<?php
// resources/views/livewire/zone-documents.blade.php

use App\Models\Zone;
use App\Models\Document;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public Zone $zone;
    public $documents = [];
    public $documentFile;
    public $selectedFolder = 'general';
    public $selectedYear = '';
    public $selectedDocumentType = '';
    public $currentViewFolder = 'all';

    // Document types for company accounts
    public $companyAccountTypes = [
        'tax_return' => 'Tax Return',
        'profit_loss' => 'Profit & Loss Statement',
        'balance_sheet' => 'Balance Sheet',
        'annual_accounts' => 'Annual Accounts',
        'management_accounts' => 'Management Accounts'
    ];

    // Document types for company claims
    public $companyClaimTypes = [
        'insurance_claim' => 'Insurance Claim',
        'warranty_claim' => 'Warranty Claim',
        'legal_claim' => 'Legal Claim',
        'compensation_claim' => 'Compensation Claim',
        'other_claim' => 'Other Claim'
    ];

    public function mount(Zone $zone)
    {
        $this->zone = $zone;
        $this->selectedYear = date('Y');

        // Initialize documents as empty collection first
        $this->documents = collect();

        // Then load documents safely
        $this->loadDocuments();
    }

    public function loadDocuments()
    {
        $this->documents = $this->zone->documents()->latest()->get();
    }

    public function uploadDocument()
    {
        $this->validate([
            'documentFile' => 'required|file|mimes:pdf,docx,txt,xlsx,xls|max:10240',
            'selectedFolder' => 'required|in:general,company_accounts,company_claims',
            'selectedYear' => 'required_if:selectedFolder,company_accounts,company_claims',
            'selectedDocumentType' => 'required_unless:selectedFolder,general',
        ]);

        try {
            \Log::info('Starting document upload', [
                'zone_id' => $this->zone->id,
                'folder' => $this->selectedFolder,
                'year' => $this->selectedYear,
                'type' => $this->selectedDocumentType
            ]);

            $originalName = $this->documentFile->getClientOriginalName();

            // Generate folder path based on selection
            $folderPath = $this->generateFolderPath();

            \Log::info('Generated folder path: ' . $folderPath);

            // Store file with organized path
            $path = $this->documentFile->storeAs(
                "{$this->zone->slug}/{$folderPath}",
                $originalName,
                'public'
            );

            \Log::info('File stored at: ' . $path);

            $document = Document::create([
                'name' => $originalName,
                'path' => $path,
                'folder_path' => $folderPath,
                'document_type' => $this->selectedDocumentType,
                'year' => $this->selectedFolder !== 'general' ? $this->selectedYear : null,
                'size' => $this->documentFile->getSize(),
                'type' => $this->documentFile->getMimeType(),
                'documentable_type' => 'App\Models\Zone',
                'documentable_id' => $this->zone->id,
            ]);

            \Log::info('Document created with ID: ' . $document->id);

            $this->reset(['documentFile', 'selectedDocumentType']);

            \Log::info('About to reload documents');

            // Reload documents after successful upload
            $this->loadDocuments();

            \Log::info('Documents reloaded successfully');

            $folderDisplayName = $this->getFolderDisplayName($folderPath);
            session()->flash('message', "Document uploaded successfully to {$folderDisplayName}");

            // Close modal
            $this->js('setTimeout(() => { $flux.modal("addDocument").close(); }, 100);');

        } catch (\Exception $e) {
            \Log::error('Document upload failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            session()->flash('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    public function generateFolderPath()
    {
        switch ($this->selectedFolder) {
            case 'company_accounts':
                return "company-accounts/{$this->selectedYear}";
            case 'company_claims':
                return "company-claims/{$this->selectedYear}";
            default:
                return 'general';
        }
    }

    public function getFolderDisplayName($folderPath)
    {
        if (empty($folderPath) || $folderPath === 'general') {
            return 'General';
        }

        if (str_starts_with($folderPath, 'company-accounts/')) {
            $year = str_replace('company-accounts/', '', $folderPath);
            return "Accounts {$year}";
        }

        if (str_starts_with($folderPath, 'company-claims/')) {
            $year = str_replace('company-claims/', '', $folderPath);
            return "Claims {$year}";
        }

        return ucfirst(str_replace('-', ' ', $folderPath));
    }

    public function setViewFolder($folder)
    {
        $this->currentViewFolder = $folder;
        $this->loadDocuments();
    }

    public function getDocumentTypeDisplay($type)
    {
        return $this->companyAccountTypes[$type] ?? $this->companyClaimTypes[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public function createNewYear($folderType, $year)
    {
        // This method can be used to create new year folders programmatically
        $folderPath = $folderType === 'accounts' ? "company-accounts/{$year}" : "company-claims/{$year}";

        // Create a placeholder document or just ensure the folder structure exists
        session()->flash('message', "Folder structure created for {$this->getFolderDisplayName($folderPath)}");

        $this->loadDocuments();
    }
}

?>

<div class="order-2 lg:order-2">
    <!-- Flash Messages -->
    @if (session('message'))
        <div class="fixed top-4 right-4 z-50 max-w-md">
            <div class="mb-3 bg-green-500 bg-opacity-20 border border-green-500 text-green-400 px-4 py-3 rounded-lg shadow-xl backdrop-blur-sm animate-slide-in">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm font-medium">{{ session('message') }}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-green-400 hover:text-green-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="fixed top-4 right-4 z-50 max-w-md">
            <div class="mb-3 bg-red-500 bg-opacity-20 border border-red-500 text-red-400 px-4 py-3 rounded-lg shadow-xl backdrop-blur-sm animate-slide-in">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-red-400 hover:text-red-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg border border-gray-700 h-full">
        <div class="p-4 md:p-6 border-b border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">Documents</h2>
                <flux:modal.trigger name="addDocument">
                    <flux:button variant="ghost" size="sm" icon="plus">
                        <span class="hidden sm:inline">Add Document</span>
                        <span class="sm:hidden">Add</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>

            <!-- Folder Navigation Tabs -->
            <div class="flex flex-wrap gap-2">
                <button
                    wire:click="setViewFolder('all')"
                    class="px-3 py-1 text-xs rounded-full transition-colors {{ $currentViewFolder === 'all' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                >
                    All Documents
                </button>
                <button
                    wire:click="setViewFolder('general')"
                    class="px-3 py-1 text-xs rounded-full transition-colors {{ $currentViewFolder === 'general' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                >
                    General
                </button>
                <button
                    wire:click="setViewFolder('company-accounts')"
                    class="px-3 py-1 text-xs rounded-full transition-colors {{ $currentViewFolder === 'company-accounts' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                >
                    Company Accounts
                </button>
                <button
                    wire:click="setViewFolder('company-claims')"
                    class="px-3 py-1 text-xs rounded-full transition-colors {{ $currentViewFolder === 'company-claims' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                >
                    Company Claims
                </button>
            </div>
        </div>

        <!-- Documents List or Empty State -->
        @if($documents && count($documents) > 0)
            <div class="divide-y divide-gray-700">
                @foreach($documents as $document)
                    <div class="p-4 hover:bg-gray-700 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>

                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-white">{{ $document->name }}</h4>
                                    <div class="flex items-center space-x-2 text-xs text-gray-400">
                                        @if($document->folder_path && $document->folder_path !== 'general')
                                            <span class="px-2 py-1 rounded">
                                                {{ $this->getFolderDisplayName($document->folder_path) }}
                                            </span>
                                        @endif

                                        <span>{{ round($document->size / 1024, 1) }} KB</span>
                                        <span>{{ $document->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="{{ Storage::url($document->path) }}" target="_blank" class="text-green-400 hover:text-green-300 text-sm transition-colors">
                                    Download
                                </a>
                                <button class="text-gray-400 hover:text-red-400 text-sm transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="p-8 md:p-12 text-center">
                <div class="w-12 h-12 md:w-16 md:h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-white mb-2">No documents yet</h3>
                <p class="text-gray-400 mb-6 text-sm md:text-base">Upload your first document to this zone.</p>
                <flux:modal.trigger name="addDocument">
                    <flux:button variant="primary" size="sm" icon="plus">
                        Upload First Document
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </div>

    <!-- Upload Document Modal -->
    <flux:modal name="addDocument" class="md:w-[500px]">
        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                        <flux:icon.document-plus class="w-5 h-5 text-white" />
                    </div>
                    <flux:heading size="lg">Upload Document</flux:heading>
                </div>
                <flux:subheading>
                    Upload a document to {{ $zone->name }}. Organize by category and year for easy management.
                </flux:subheading>
            </div>

            <!-- Form Section -->
            <form wire:submit="uploadDocument" class="space-y-6" enctype="multipart/form-data">
                <!-- Folder Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Document Category *</flux:label>
                        <flux:select wire:model.live="selectedFolder" required>
                            <option value="general">General Documents</option>
                            <option value="company_accounts">Company Accounts</option>
                            <option value="company_claims">Company Claims</option>
                        </flux:select>
                        <flux:error name="selectedFolder" />
                    </flux:field>

                    @if($selectedFolder !== 'general')
                        <flux:field>
                            <flux:label>Year *</flux:label>
                            <flux:select wire:model.live="selectedYear" required>
                                @for($year = 2030; $year >= 2020; $year--)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endfor
                            </flux:select>
                            <flux:error name="selectedYear" />
                        </flux:field>
                    @endif
                </div>

                <!-- Document Type Selection -->
                @if($selectedFolder === 'company_accounts')
                    <flux:field>
                        <flux:label>Document Type *</flux:label>
                        <flux:select wire:model="selectedDocumentType" required>
                            <option value="">Select document type...</option>
                            @foreach($companyAccountTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="selectedDocumentType" />
                    </flux:field>
                @elseif($selectedFolder === 'company_claims')
                    <flux:field>
                        <flux:label>Claim Type *</flux:label>
                        <flux:select wire:model="selectedDocumentType" required>
                            <option value="">Select claim type...</option>
                            @foreach($companyClaimTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="selectedDocumentType" />
                    </flux:field>
                @endif

                <!-- File Upload -->
                <flux:field>
                    <flux:label>Select Document</flux:label>
                    <input
                        type="file"
                        wire:model.live="documentFile"
                        accept=".pdf,.docx,.txt,.xlsx,.xls"
                        required
                        class="w-full px-3 py-2 bg-gray-700 rounded-md border border-gray-600 text-white file:bg-green-500 file:text-white file:border-0 file:rounded-md file:px-3 file:py-1 file:mr-3 file:text-sm hover:file:bg-green-600 transition-colors"
                    />
                    <flux:error name="documentFile" />
                    <div class="text-xs text-gray-400 mt-1">
                        Supported formats: PDF, Word, Excel, Text files (max 10MB)
                    </div>

                    <!-- File Processing Indicator -->
                    <div wire:loading wire:target="documentFile" class="mt-2">
                        <div class="flex items-center text-sm text-blue-400">
                            <flux:icon.arrow-path class="animate-spin w-4 h-4 mr-2" />
                            Processing file...
                        </div>
                    </div>

                    <!-- File Preview -->
                    @if($documentFile)
                        <div class="mt-2 p-3 bg-gray-100 dark:bg-gray-700 rounded-md">
                            <div class="flex items-center text-sm text-green-600 dark:text-green-400">
                                <flux:icon.document class="w-4 h-4 mr-2 flex-shrink-0" />
                                <span class="truncate">{{ $documentFile->getClientOriginalName() }}</span>
                                <span class="ml-auto text-gray-500 dark:text-gray-400 flex-shrink-0">({{ round($documentFile->getSize() / 1024, 1) }} KB)</span>
                            </div>

                            <!-- Destination Preview -->
                            @if($selectedFolder !== 'general')
                                <div class="text-xs text-gray-500 mt-2 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                    Will be saved to: {{ $this->getFolderDisplayName($this->generateFolderPath()) }}
                                </div>
                            @endif
                        </div>
                    @endif
                </flux:field>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="cloud-arrow-up"
                        wire:loading.attr="disabled"
                        wire:target="uploadDocument"
                    >
                        <span wire:loading.remove wire:target="uploadDocument">Upload Document</span>
                        <span wire:loading wire:target="uploadDocument">Uploading...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <style>
        @keyframes slide-in {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        .animate-slide-in { animation: slide-in 0.3s ease-out; }
        .bg-gray-750 { background-color: #374151; }
    </style>

    <script>
        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const flashMessages = document.querySelectorAll('.fixed.top-4.right-4 > div');
            flashMessages.forEach(function(message) {
                message.style.opacity = '0';
                message.style.transform = 'translateX(100%)';
                setTimeout(() => message.remove(), 300);
            });
        }, 5000);
    </script>
</div>

