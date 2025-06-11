<?php
// resources/views/livewire/building-documents.blade.php

use App\Models\Building;
use App\Models\Document;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public Building $building;
    public $documents = [];
    public $documentFile;

    public function mount(Building $building)
    {
        $this->building = $building;
        $this->loadDocuments();
    }

    public function loadDocuments()
    {
        $this->documents = $this->building->documents()->latest()->get();
    }

    public function uploadDocument()
    {
        // Debug: Check if file is received
        if (!$this->documentFile) {
            session()->flash('error', 'No file was received. Please try again.');
            return;
        }

        $this->validate([
            'documentFile' => 'required|file|mimes:pdf,docx,txt|max:10240', // 10MB max
        ]);

        try {
            // Debug logging
            \Log::info('Building file upload attempt:', [
                'original_name' => $this->documentFile->getClientOriginalName(),
                'size' => $this->documentFile->getSize(),
                'mime_type' => $this->documentFile->getMimeType(),
                'building_slug' => $this->building->slug,
            ]);

            $originalName = $this->documentFile->getClientOriginalName();
            $path = $this->documentFile->storeAs("/{$this->building->zone->slug}/buildings/{$this->building->slug}", $originalName, 'public');

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
                'documentable_type' => Building::class,
                'documentable_id' => $this->building->id,
            ]);


            $this->reset(['documentFile']);
            $this->loadDocuments();

            session()->flash('message', 'Document "' . $originalName . '" uploaded successfully!');

            $this->js('setTimeout(() => { $flux.modal("addBuildingDocument").close(); }, 100);');

        } catch (\Exception $e) {
            \Log::error('Building document upload failed: ' . $e->getMessage(), [
                'file' => $this->documentFile ? $this->documentFile->getClientOriginalName() : 'null',
                'building' => $this->building->slug,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to upload document: ' . $e->getMessage());
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
                <h2 class="text-lg font-semibold text-white">Documents</h2>
                <flux:modal.trigger name="addBuildingDocument">
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
                                <div>
                                    <h4 class="text-sm font-medium text-white">{{ $document->name }}</h4>
                                    <p class="text-xs text-gray-400">{{ round($document->size / 1024, 1) }} KB • {{ $document->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <a href="{{ Storage::url($document->path) }}" target="_blank" class="text-green-400 hover:text-green-300 text-sm transition-colors">
                                Download
                            </a>
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
                <p class="text-gray-400 mb-6 text-sm md:text-base">Upload your first document to this building.</p>
                <flux:modal.trigger name="addBuildingDocument">
                    <flux:button variant="primary" size="sm" icon="plus">
                        Upload First Document
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </div>

    <!-- Upload Document Modal -->
    <flux:modal name="addBuildingDocument" class="md:w-96">
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
                    Select a document to upload to {{ $building->name }}. Supported formats: PDF, DOCX, TXT (max 10MB)
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
                        accept=".pdf,.docx,.txt"
                        required
                        class="w-full px-3 py-2 bg-gray-700 rounded-md border border-gray-600 text-white file:bg-green-500 file:text-white file:border-0 file:rounded-md file:px-3 file:py-1 file:mr-3 file:text-sm hover:file:bg-green-600 transition-colors"
                    />
                    <flux:error name="documentFile" />

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
                            <!-- Debug info -->
                            <div class="text-xs text-gray-500 mt-1">
                                Type: {{ $documentFile->getMimeType() }} |
                                Temp path: {{ $documentFile->getRealPath() }}
                            </div>
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

