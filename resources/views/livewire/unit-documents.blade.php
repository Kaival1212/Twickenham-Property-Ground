<?php
// resources/views/livewire/unit-documents.blade.php

use App\Models\Unit;
use App\Models\Document;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public Unit $unit;
    public $documents = [];
    public $documentFile;
    public $visibleToTenants = 'no';

    public function mount(Unit $unit)
    {
        $this->unit = $unit;
        $this->loadDocuments();
    }

    public function loadDocuments()
    {
        $this->documents = $this->unit->documents()->latest()->get();
    }

    public function uploadDocument()
    {
        // Debug: Check if file is received
        if (!$this->documentFile) {
            session()->flash('error', 'No file was received. Please try again.');
            return;
        }

        $this->validate([
            'documentFile' => 'required|file|mimes:pdf,docx,txt,jpg,jpeg,png|max:10240', // 10MB max
            'visibleToTenants' => 'required|in:yes,no',
        ]);

        try {
            // Debug logging
            \Log::info('Unit file upload attempt:', [
                'original_name' => $this->documentFile->getClientOriginalName(),
                'size' => $this->documentFile->getSize(),
                'mime_type' => $this->documentFile->getMimeType(),
                'unit_slug' => $this->unit->slug,
                'visible_to_tenants' => $this->visibleToTenants,
            ]);

            $originalName = $this->documentFile->getClientOriginalName();
            $path = $this->documentFile->storeAs("/{$this->unit->building->zone->slug}/buildings/{$this->unit->building->slug}/units/{$this->unit->slug}", $originalName, 'public');

            // Debug: Check if file was actually stored
            if (!$path) {
                throw new \Exception('File storage failed - no path returned');
            }

            // Verify file exists
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('File was not saved to storage');
            }

            $document = Document::create([
                'name' => $originalName,
                'path' => $path,
                'size' => $this->documentFile->getSize(),
                'type' => $this->documentFile->getMimeType(),
                'visible_to_tenants' => $this->visibleToTenants,
                'documentable_type' => Unit::class,
                'documentable_id' => $this->unit->id,
            ]);

            $this->reset(['documentFile']);
            $this->visibleToTenants = 'no'; // Reset to default
            $this->loadDocuments();

            session()->flash('message', 'Document "' . $originalName . '" uploaded successfully!');

            $this->js('setTimeout(() => { $flux.modal("addUnitDocument").close(); }, 100);');

        } catch (\Exception $e) {
            \Log::error('Unit document upload failed: ' . $e->getMessage(), [
                'file' => $this->documentFile ? $this->documentFile->getClientOriginalName() : 'null',
                'unit' => $this->unit->slug,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    public function toggleTenantVisibility($documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            $document->visible_to_tenants = $document->visible_to_tenants === 'yes' ? 'no' : 'yes';
            $document->save();

            $this->loadDocuments();

            $status = $document->visible_to_tenants === 'yes' ? 'visible to' : 'hidden from';
            session()->flash('message', "Document is now {$status} tenants.");

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update document visibility.');
        }
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
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">Unit Documents</h2>
                <flux:modal.trigger name="addUnitDocument">
                    <flux:button variant="ghost" size="sm" icon="plus">
                        <span class="hidden sm:inline">Add Document</span>
                        <span class="sm:hidden">Add</span>
                    </flux:button>
                </flux:modal.trigger>
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
                                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-medium text-white">{{ $document->name }}</h4>
                                        @if($document->visible_to_tenants === 'yes')
                                            <span class="text-xs bg-blue-500 bg-opacity-20 text-blue-400 px-2 py-1 rounded-full">
                                                Visible to tenants
                                            </span>
                                        @else
                                            <span class="text-xs bg-gray-500 bg-opacity-20 text-gray-400 px-2 py-1 rounded-full">
                                                Private
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400">{{ round($document->size / 1024, 1) }} KB • {{ $document->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <!-- Toggle Visibility Button -->
                                <button
                                    wire:click="toggleTenantVisibility({{ $document->id }})"
                                    class="text-blue-400 hover:text-blue-300 text-xs transition-colors p-1"
                                    title="{{ $document->visible_to_tenants === 'yes' ? 'Hide from tenants' : 'Show to tenants' }}"
                                >
                                    @if($document->visible_to_tenants === 'yes')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                        </svg>
                                    @endif
                                </button>

                                <!-- Download Button -->
                                <a href="{{ Storage::url($document->path) }}" target="_blank" class="text-green-400 hover:text-green-300 text-sm transition-colors">
                                    Download
                                </a>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-white mb-2">No documents yet</h3>
                <p class="text-gray-400 mb-6 text-sm md:text-base">Upload your first document to this unit.</p>
                <flux:modal.trigger name="addUnitDocument">
                    <flux:button variant="primary" size="sm" icon="plus">
                        Upload First Document
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </div>

    <!-- Upload Document Modal -->
    <flux:modal name="addUnitDocument" class="md:w-96">
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
                    Select a document to upload to {{ $unit->name }}. Choose if tenants can view this document.
                </flux:subheading>
            </div>

            <!-- Form Section -->
            <form wire:submit="uploadDocument" class="space-y-6" enctype="multipart/form-data">
                <!-- File Upload -->
                <flux:field>
                    <flux:label>Select Document</flux:label>
                    <input
                        type="file"
                        wire:model.live="documentFile"
                        accept=".pdf,.docx,.txt,.jpg,.jpeg,.png"
                        required
                        class="w-full px-3 py-2 bg-gray-700 rounded-md border border-gray-600 text-white file:bg-green-500 file:text-white file:border-0 file:rounded-md file:px-3 file:py-1 file:mr-3 file:text-sm hover:file:bg-green-600 transition-colors"
                    />
                    <flux:error name="documentFile" />
                    <flux:description>Supported: PDF, DOCX, TXT, JPG, PNG (max 10MB)</flux:description>

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
                        </div>
                    @endif
                </flux:field>

                <!-- Tenant Visibility -->
                <flux:field>
                    <flux:label>Tenant Access</flux:label>
                    <flux:select wire:model="visibleToTenants" required>
                        <option value="no">Private (Property managers only)</option>
                        <option value="yes">Visible to tenants</option>
                    </flux:select>
                    <flux:description>Choose whether tenants can view and download this document</flux:description>
                    <flux:error name="visibleToTenants" />
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
