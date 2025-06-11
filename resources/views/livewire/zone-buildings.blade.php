<?php
// resources/views/livewire/zone-buildings.blade.php

use App\Models\Zone;
use App\Models\Building;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new class extends Component
{
    public Zone $zone;
    public $buildings = [];

    public $buildingName = '';
    public $buildingStreet = '';

    public function mount(Zone $zone)
    {
        $this->zone = $zone;
        $this->loadBuildings();
    }

    public function loadBuildings()
    {
        $this->buildings = $this->zone->buildings()->latest()->get();
    }

    public function addBuilding()
    {
        $this->validate([
            'buildingName' => 'required|string|max:255',
            'buildingStreet' => 'nullable|string|max:255',
        ]);

        try {
            Building::create([
                'name' => $this->buildingName,
                'slug' => Str::slug($this->buildingName . '-' . $this->buildingStreet),
                'zone_id' => $this->zone->id,
                'street' => $this->buildingStreet,
            ]);

            // Reset form fields
            $this->buildingName = '';
            $this->buildingStreet = '';
            // Reload buildings
            $this->loadBuildings();
            session()->flash('message', 'Building "' . $this->buildingName . '" added successfully!');
            $this->dispatch('building-added');

            // Close the modal
            $this->dispatch('close-modal', 'addBuilding');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add building. Please try again.');
        }
    }
}

?>

<div class="order-1 lg:order-1">
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
                <h2 class="text-lg font-semibold text-white">Buildings</h2>
                <flux:modal.trigger name="addBuilding">
                    <flux:button variant="ghost" size="sm" icon="plus">
                        <span class="hidden sm:inline">Add Building</span>
                        <span class="sm:hidden">Add</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- buildings List or Empty State -->
        @if($buildings && count($buildings) > 0)
            <div class="divide-y divide-gray-700">
                @foreach($buildings as $building)
                    <a href="/manager/buildings/{{$building->slug}}">
                    <div class="p-4 hover:bg-gray-700 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m-2 0h2M7 7h10M7 10h10M7 13h7"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-white">{{ $building->name }}</h4>
                                    <p class="text-xs text-gray-400">
                                        {{ $building->street ?? 'No street address' }} •
                                        {{ $building->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs bg-green-500 bg-opacity-20 text-green-400 px-2 py-1 rounded">
                                    Active
                                </span>
                                <button class="text-gray-400 hover:text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    </a>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="p-8 md:p-12 text-center">
                <div class="w-12 h-12 md:w-16 md:h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m-2 0h2M7 7h10M7 10h10M7 13h7"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-white mb-2">No buildings yet</h3>
                <p class="text-gray-400 mb-6 text-sm md:text-base">Add your first building to this zone.</p>
                <flux:modal.trigger name="addBuilding">
                    <flux:button variant="primary" size="sm" icon="plus">
                        Add First Building
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </div>

    <!-- Add Building Modal -->
    <flux:modal name="addBuilding" class="md:w-96">
        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                        <flux:icon.building-office class="w-5 h-5 text-white" />
                    </div>
                    <flux:heading size="lg">Add New Building</flux:heading>
                </div>
                <flux:subheading>
                    Add a new building to {{ $zone->name }}. Enter the building name and optional street address.
                </flux:subheading>
            </div>

            <!-- Form Section -->
            <form wire:submit="addBuilding" class="space-y-6">
                <!-- Building Name -->
                <flux:field>
                    <flux:label>Building Name *</flux:label>
                    <flux:input
                        wire:model="buildingName"
                        placeholder="e.g., Tower Block A, Main Building..."
                        required
                    />
                    <flux:error name="buildingName" />
                </flux:field>

                <!-- Street Address -->
                <flux:field>
                    <flux:label>Street Address</flux:label>
                    <flux:input
                        wire:model="buildingStreet"
                        placeholder="e.g., 123 Main Street, London..."
                    />
                    <flux:error name="buildingStreet" />
                </flux:field>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary" >
                        Add Building
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

