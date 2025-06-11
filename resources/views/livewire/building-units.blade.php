<?php
// resources/views/livewire/building-units.blade.php

use App\Models\Building;
use App\Models\Unit;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new class extends Component
{
    public Building $building;
    public $units = [];
    public $unitName = '';
    public $unitType = '';
    public $unitAddress = '';
    public $unitPostcode = '';
    public $unitVacancy = 'available';

    public function mount(Building $building)
    {
        $this->building = $building;
        $this->loadUnits();
    }

    public function loadUnits()
    {
        $this->units = $this->building->units()->latest()->get();
    }

    public function addUnit()
    {
        $this->validate([
            'unitName' => 'required|string|max:255',
            'unitType' => 'nullable|string|max:255',
            'unitAddress' => 'nullable|string|max:255',
            'unitPostcode' => 'nullable|string|max:10',
            'unitVacancy' => 'required|in:available,unavailable,pending',
        ]);

        try {
            Unit::create([
                'name' => $this->unitName,
                'slug' => Str::slug($this->unitName . '-' . $this->building->slug . '-' . time()),
                'building_id' => $this->building->id,
                'type' => $this->unitType,
                'address' => $this->unitAddress,
                'postcode' => $this->unitPostcode,
                'vacancy' => $this->unitVacancy,
            ]);

            $this->reset(['unitName', 'unitType', 'unitAddress', 'unitPostcode']);
            $this->unitVacancy = 'available'; // Reset to default
            $this->loadUnits();

            session()->flash('message', 'Unit "' . $this->unitName . '" added successfully!');

            // Use JavaScript to close modal after a short delay
            $this->js('setTimeout(() => { $flux.modal("addUnit").close(); }, 100);');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add unit. Please try again.');
            \Log::error('Unit creation failed: ' . $e->getMessage());
        }
    }

    public function getVacancyColor($vacancy)
    {
        return match($vacancy) {
            'available' => 'bg-green-500 text-green-400',
            'unavailable' => 'bg-red-500 text-red-400',
            'pending' => 'bg-yellow-500 text-yellow-400',
            default => 'bg-gray-500 text-gray-400'
        };
    }

    public function getVacancyText($vacancy)
    {
        return match($vacancy) {
            'available' => 'Available',
            'unavailable' => 'Occupied',
            'pending' => 'Pending',
            default => 'Unknown'
        };
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
                <h2 class="text-lg font-semibold text-white">Units</h2>
                <flux:modal.trigger name="addUnit">
                    <flux:button variant="ghost" size="sm" icon="plus">
                        <span class="hidden sm:inline">Add Unit</span>
                        <span class="sm:hidden">Add</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Units List or Empty State -->
        @if($units && count($units) > 0)
            <div class="divide-y divide-gray-700">
                @foreach($units as $unit)
                    <a href="/manager/units/{{$unit->slug}}">
                    <div class="p-4 hover:bg-gray-700 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-white">{{ $unit->name }}</h4>
                                    <p class="text-xs text-gray-400">
                                        {{ $unit->type ?? 'No type specified' }} •
                                        {{ $unit->address ?? 'No address' }} •
                                        {{ $unit->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs {{ $this->getVacancyColor($unit->vacancy) }} bg-opacity-20 px-2 py-1 rounded">
                                    {{ $this->getVacancyText($unit->vacancy) }}
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-white mb-2">No units yet</h3>
                <p class="text-gray-400 mb-6 text-sm md:text-base">Add your first unit to this building.</p>
                <flux:modal.trigger name="addUnit">
                    <flux:button variant="primary" size="sm" icon="plus">
                        Add First Unit
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </div>

    <!-- Add Unit Modal -->
    <flux:modal name="addUnit" class="md:w-[500px]">
        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                        <flux:icon.squares-plus class="w-5 h-5 text-white" />
                    </div>
                    <flux:heading size="lg">Add New Unit</flux:heading>
                </div>
                <flux:subheading>
                    Add a new unit to {{ $building->name }}. Enter the unit details and availability status.
                </flux:subheading>
            </div>

            <!-- Form Section -->
            <form wire:submit="addUnit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Unit Name -->
                    <flux:field>
                        <flux:label>Unit Name *</flux:label>
                        <flux:input
                            wire:model="unitName"
                            placeholder="e.g., Apt 101, Unit A..."
                            required
                        />
                        <flux:error name="unitName" />
                    </flux:field>

                    <!-- Unit Type -->
                    <flux:field>
                        <flux:label>Unit Type</flux:label>
                        <flux:input
                            wire:model="unitType"
                            placeholder="e.g., 1-bed, Studio, Office..."
                        />
                        <flux:error name="unitType" />
                    </flux:field>
                </div>

                <!-- Address -->
                <flux:field>
                    <flux:label>Address</flux:label>
                    <flux:input
                        wire:model="unitAddress"
                        placeholder="e.g., Floor 2, Building A..."
                    />
                    <flux:error name="unitAddress" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Postcode -->
                    <flux:field>
                        <flux:label>Postcode</flux:label>
                        <flux:input
                            wire:model="unitPostcode"
                            placeholder="e.g., SW1A 1AA"
                        />
                        <flux:error name="unitPostcode" />
                    </flux:field>

                    <!-- Vacancy Status -->
                    <flux:field>
                        <flux:label>Availability Status *</flux:label>
                        <flux:select wire:model="unitVacancy" required>
                            <option value="available">Available</option>
                            <option value="unavailable">Occupied</option>
                            <option value="pending">Pending</option>
                        </flux:select>
                        <flux:error name="unitVacancy" />
                    </flux:field>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button
                        type="submit"
                        variant="primary"
                        wire:loading.attr="disabled"
                        wire:target="addUnit"
                    >
                        <span wire:loading.remove wire:target="addUnit">Add Unit</span>
                        <span wire:loading wire:target="addUnit">Adding...</span>
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

