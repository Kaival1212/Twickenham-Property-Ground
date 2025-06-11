<?php

use function Laravel\Folio\name;
use App\Models\Building;
use App\Models\Zone;
use Livewire\Volt\Component;

name('building.index');

\Laravel\Folio\middleware(['auth', 'verified']);

new class extends Component
{
    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $selectedZone = '';
    public $perPage = 12;

    public $buildings;
    public $zones;
    public $totalBuildings;
    public $totalUnits;
    public $averageOccupancy;

    public function mount()
    {
        $this->zones = Zone::orderBy('name')->get();
        $this->loadBuildings();
        $this->loadStats();
    }

    public function loadBuildings()
    {
        $query = Building::with(['zone', 'units']);

        // Search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('street', 'like', '%' . $this->search . '%')
                    ->orWhereHas('zone', function($zq) {
                        $zq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Zone filter
        if ($this->selectedZone) {
            $query->where('zone_id', $this->selectedZone);
        }

        // Sorting
        if ($this->sortBy === 'zone') {
            $query->join('zones', 'buildings.zone_id', '=', 'zones.id')
                ->orderBy('zones.name', $this->sortDirection)
                ->select('buildings.*');
        } elseif ($this->sortBy === 'units_count') {
            $query->withCount('units')
                ->orderBy('units_count', $this->sortDirection);
        } elseif ($this->sortBy === 'occupancy') {
            $query->withCount(['units', 'units as occupied_units_count' => function($q) {
                $q->where('vacancy', 'unavailable');
            }])->get()->sortBy(function($building) {
                $total = $building->units_count;
                return $total > 0 ? ($building->occupied_units_count / $total) : 0;
            }, SORT_REGULAR, $this->sortDirection === 'desc');

            $this->buildings = $query->take($this->perPage);
            return;
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $this->buildings = $query->take($this->perPage)->get();
    }

    public function loadStats()
    {
        $this->totalBuildings = Building::count();
        $this->totalUnits = \App\Models\Unit::count();

        $totalUnitsCount = \App\Models\Unit::count();
        $occupiedUnitsCount = \App\Models\Unit::where('vacancy', 'unavailable')->count();
        $this->averageOccupancy = $totalUnitsCount > 0 ? round(($occupiedUnitsCount / $totalUnitsCount) * 100) : 0;
    }

    public function updatedSearch()
    {
        $this->loadBuildings();
    }

    public function updatedSelectedZone()
    {
        $this->loadBuildings();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadBuildings();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedZone = '';
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->loadBuildings();
    }

    public function getOccupancyRate($building)
    {
        $totalUnits = $building->units->count();
        if ($totalUnits === 0) return 0;

        $occupiedUnits = $building->units->where('vacancy', 'unavailable')->count();
        return round(($occupiedUnits / $totalUnits) * 100);
    }

    public function getOccupancyColor($rate)
    {
        if ($rate >= 90) return 'text-green-400 bg-green-500';
        if ($rate >= 70) return 'text-yellow-400 bg-yellow-500';
        if ($rate >= 50) return 'text-orange-400 bg-orange-500';
        return 'text-red-400 bg-red-500';
    }
}

?>

@volt
<div>
<x-layouts.app :title="__('Buildings')">
    <div class="bg-gray-900 min-h-screen">
        <div class="flex flex-col gap-6 p-4 md:p-6">

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">Buildings</h1>
                    <p class="text-sm text-gray-400 mt-1">Manage and view all buildings across zones</p>
                </div>

                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" size="sm" wire:click="loadBuildings" icon="arrow-path">
                        Refresh
                    </flux:button>
                    <flux:button variant="primary" size="sm" icon="plus">
                        Add Building
                    </flux:button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-400">Total Buildings</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ $totalBuildings }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m-2 0h2M7 7h10M7 10h10M7 13h7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-400">Total Units</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ $totalUnits }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-400">Average Occupancy</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ $averageOccupancy }}%</p>
                        </div>
                        <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Controls -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <div class="flex flex-col lg:flex-row gap-4 items-center">
                    <!-- Search -->
                    <div class="flex-1 w-full lg:w-auto">
                        <flux:field>
                            <flux:input
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search buildings, zones, or addresses..."
                                icon="magnifying-glass"
                                class="w-full"
                            />
                        </flux:field>
                    </div>

                    <!-- Zone Filter -->
                    <div class="w-full lg:w-48">
                        <flux:field>
                            <flux:select wire:model.live="selectedZone" placeholder="All Zones">
                                <option value="">All Zones</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>

                    <!-- Sort Controls -->
                    <div class="flex items-center gap-2">
                        <flux:button
                            variant="{{ $sortBy === 'name' ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:click="sortBy('name')"
                        >
                            Name
                            @if($sortBy === 'name')
                                <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                            @endif
                        </flux:button>

                        <flux:button
                            variant="{{ $sortBy === 'zone' ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:click="sortBy('zone')"
                        >
                            Zone
                            @if($sortBy === 'zone')
                                <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                            @endif
                        </flux:button>

                        <flux:button
                            variant="{{ $sortBy === 'units_count' ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:click="sortBy('units_count')"
                        >
                            Units
                            @if($sortBy === 'units_count')
                                <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                            @endif
                        </flux:button>

                        @if($search || $selectedZone || $sortBy !== 'name' || $sortDirection !== 'asc')
                            <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                                Clear
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Buildings Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($buildings as $building)
                    @php
                        $occupancyRate = $this->getOccupancyRate($building);
                        $occupancyColorClasses = $this->getOccupancyColor($occupancyRate);
                    @endphp

                    <div class="bg-gray-800 rounded-lg border border-gray-700 hover:border-green-500 transition-all duration-200 group cursor-pointer">
                        <a href="buildings/{{$building->slug}}" class="block p-6">
                            <!-- Building Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center group-hover:bg-opacity-30 transition-colors">
                                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m-2 0h2M7 7h10M7 10h10M7 13h7"/>
                                    </svg>
                                </div>
                                <span class="text-xs {{ $occupancyColorClasses }} bg-opacity-20 px-2 py-1 rounded-full">
                                    {{ $occupancyRate }}% occupied
                                </span>
                            </div>

                            <!-- Building Info -->
                            <div class="space-y-2">
                                <h3 class="text-lg font-semibold text-white group-hover:text-green-400 transition-colors">
                                    {{ $building->name }}
                                </h3>

                                <p class="text-sm text-gray-400">
                                    {{ $building->zone->name }}
                                </p>

                                @if($building->street)
                                    <p class="text-xs text-gray-500 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $building->street }}
                                    </p>
                                @endif
                            </div>

                            <!-- Building Stats -->
                            <div class="mt-4 pt-4 border-t border-gray-700">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center space-x-4">
                                        <span class="text-gray-400">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                            </svg>
                                            {{ $building->units->count() }} units
                                        </span>

                                        <span class="text-gray-400">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            {{ $building->documents->count() }} docs
                                        </span>
                                    </div>

                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-green-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <!-- Empty State -->
                    <div class="col-span-full">
                        <div class="bg-gray-800 rounded-lg border border-gray-700 p-12 text-center">
                            <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m-2 0h2M7 7h10M7 10h10M7 13h7"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-white mb-2">
                                @if($search || $selectedZone)
                                    No buildings found
                                @else
                                    No buildings yet
                                @endif
                            </h3>
                            <p class="text-gray-400 mb-6">
                                @if($search || $selectedZone)
                                    Try adjusting your search criteria or filters.
                                @else
                                    Get started by adding your first building.
                                @endif
                            </p>
                            @if($search || $selectedZone)
                                <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark">
                                    Clear Filters
                                </flux:button>
                            @else
                                <flux:button variant="primary" icon="plus">
                                    Add First Building
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Loading States -->
            <div wire:loading.delay wire:target="search,selectedZone,sortBy" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
                    <svg class="animate-spin w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-white">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
</div>
@endvolt
