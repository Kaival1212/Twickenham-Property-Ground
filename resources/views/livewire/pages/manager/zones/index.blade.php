<?php

use function Laravel\Folio\name;

\Laravel\Folio\middleware(['auth', 'verified']);

use Livewire\Volt\Component;

new class extends Component {

    public $zones = [];
    public $name = '';

    public function mount()
    {
        $this->zones = \App\Models\Zone::all();
    }

    public function addZone()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:zones,name'
        ]);

        $slug = \Illuminate\Support\Str::slug($this->name);

        \App\Models\Zone::create([
            'name' => $this->name,
            'slug' => $slug,
        ]);

        $this->zones = \App\Models\Zone::all();
        $this->name = '';

        // Close the modal after successful creation
        $this->dispatch('close-modal');
        session()->flash('message', 'Zone created successfully!');
    }

};

name('zones.index');
?>

@volt

<div class="w-full h-full bg-gray-900 min-h-screen">
    <x-layouts.app>
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Property Zones</h1>
                        <p class="text-sm text-gray-400 mt-2">Manage and organize your property zones across different locations</p>
                    </div>

                    <!-- Add Zone Modal -->
                    <flux:modal name="add-zone" class="bg-gray-900">
                        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700 max-w-md mx-auto">
                            <div class="space-y-6">
                                <!-- Modal Header -->
                                <div class="mb-6">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <flux:heading size="lg" class="text-white font-semibold">
                                            Create New Zone
                                        </flux:heading>
                                    </div>
                                    <flux:text class="text-gray-300 text-sm leading-relaxed">
                                        Add a new zone to organize your properties by location or category.
                                    </flux:text>
                                </div>

                                <!-- Form -->
                                <form wire:submit="addZone" class="space-y-6">
                                    <flux:field>
                                        <flux:label class="text-gray-300 font-medium">Zone Name</flux:label>
                                        <flux:input
                                            wire:model="name"
                                            placeholder="Enter zone name (e.g., Downtown District, North Side)"
                                            class="bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:border-green-500 focus:ring-green-500 mt-2"
                                        />
                                        <flux:error name="name" class="text-red-400 text-sm mt-1" />
                                    </flux:field>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-600">
                                        <flux:button
                                            type="button"
                                            variant="ghost"
                                            x-on:click="$flux.modal('add-zone').close()"
                                            class="text-gray-300 hover:text-white hover:bg-gray-700 px-4 py-2 transition-colors"
                                        >
                                            Cancel
                                        </flux:button>
                                        <flux:button
                                            type="submit"
                                            variant="primary"
                                            icon="check"
                                            x-on:click="setTimeout(() => $flux.modal('add-zone').close(), 100)"
                                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 font-medium transition-colors shadow-lg"
                                        >
                                            Create Zone
                                        </flux:button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </flux:modal>

                    <!-- Add Zone Trigger Button -->
                    <flux:modal.trigger name="add-zone">
                        <flux:button
                            icon="plus"
                            variant="primary"
                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 font-medium rounded-lg shadow-lg transition-all duration-200 hover:shadow-xl transform hover:-translate-y-0.5"
                        >
                            Add Zone
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>

            <!-- Success Message -->
            @if (session()->has('message'))
                <div class="mb-6 bg-green-500 bg-opacity-20 border border-green-500 text-green-400 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ session('message') }}
                    </div>
                </div>
            @endif

            <!-- Zones Grid -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($zones as $zone)
                    <a
                        href="zones/{{$zone['slug']}}"
                        class="group block bg-gray-800 rounded-lg border border-gray-700 hover:border-green-500 hover:shadow-xl transition-all duration-300 p-6 transform hover:-translate-y-1"
                    >
                        <!-- Zone Card Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 bg-gray-700 rounded-lg flex items-center justify-center group-hover:bg-green-500 group-hover:shadow-lg transition-all duration-300">
                                <svg class="w-7 h-7 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Zone Information -->
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-white mb-2 group-hover:text-green-400 transition-colors">
                                {{ $zone['name'] }}
                            </h3>
                            <div class="flex items-center justify-between">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-gray-700 text-gray-300 font-mono group-hover:bg-green-500 group-hover:text-white transition-colors">
                                {{ $zone['slug'] }}
                            </span>
                            </div>
                        </div>

                        <!-- Zone Stats Preview -->
                        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-gray-700">
                            <div class="text-center">
                                <div class="text-lg font-bold text-white">12</div>
                                <div class="text-xs text-gray-400">Buildings</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-white">48</div>
                                <div class="text-xs text-gray-400">Units</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Empty State -->
            @if($zones->isEmpty())
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6 border border-gray-700">
                        <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-3">No zones created yet</h3>
                    <p class="text-gray-400 mb-8 max-w-md mx-auto">
                        Get started by creating your first property zone. Zones help you organize and manage properties by location or category.
                    </p>
                    <flux:modal.trigger name="add-zone">
                        <flux:button
                            icon="plus"
                            variant="primary"
                            class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 font-medium rounded-lg shadow-lg transition-all duration-200 hover:shadow-xl"
                        >
                            Create Your First Zone
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            @endif

            <!-- Zone Statistics Overview -->
            @if(!$zones->isEmpty())
                <div class="mt-12 bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Zone Overview</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-400 mb-1">{{ $zones->count() }}</div>
                            <div class="text-sm text-gray-400">Total Zones</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-400 mb-1">156</div>
                            <div class="text-sm text-gray-400">Total Buildings</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-400 mb-1">642</div>
                            <div class="text-sm text-gray-400">Total Units</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-400 mb-1">94%</div>
                            <div class="text-sm text-gray-400">Avg Occupancy</div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </x-layouts.app>
</div>
@endvolt
