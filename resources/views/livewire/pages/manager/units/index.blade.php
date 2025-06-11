<?php

use function Laravel\Folio\name;
use App\Models\Unit;
use App\Models\Building;
use App\Models\Zone;
use Livewire\Volt\Component;

name('units.index');

\Laravel\Folio\middleware(['auth', 'verified']);

new class extends Component
{
    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $selectedBuilding = '';
    public $selectedZone = '';
    public $selectedVacancy = '';
    public $selectedType = '';
    public $perPage = 12;

    public $units;
    public $buildings;
    public $zones;
    public $types;
    public $totalUnits;
    public $availableUnits;
    public $occupancyRate;

    public function mount()
    {
        $this->buildings = Building::with('zone')->orderBy('name')->get();
        $this->zones = Zone::orderBy('name')->get();
        $this->types = Unit::whereNotNull('type')->distinct('type')->pluck('type')->sort();
        $this->loadUnits();
        $this->loadStats();
    }

    public function loadUnits()
    {
        $query = Unit::with(['building', 'building.zone']);

        // Search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('address', 'like', '%' . $this->search . '%')
                    ->orWhere('postcode', 'like', '%' . $this->search . '%')
                    ->orWhereHas('building', function($bq) {
                        $bq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Building filter
        if ($this->selectedBuilding) {
            $query->where('building_id', $this->selectedBuilding);
        }

        // Zone filter
        if ($this->selectedZone) {
            $query->whereHas('building', function($bq) {
                $bq->where('zone_id', $this->selectedZone);
            });
        }

        // Vacancy filter
        if ($this->selectedVacancy) {
            $query->where('vacancy', $this->selectedVacancy);
        }

        // Type filter
        if ($this->selectedType) {
            $query->where('type', $this->selectedType);
        }

        // Sorting
        if ($this->sortBy === 'building') {
            $query->join('buildings', 'units.building_id', '=', 'buildings.id')
                ->orderBy('buildings.name', $this->sortDirection)
                ->select('units.*');
        } elseif ($this->sortBy === 'zone') {
            $query->join('buildings', 'units.building_id', '=', 'buildings.id')
                ->join('zones', 'buildings.zone_id', '=', 'zones.id')
                ->orderBy('zones.name', $this->sortDirection)
                ->select('units.*');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $this->units = $query->take($this->perPage)->get();
    }

    public function loadStats()
    {
        $this->totalUnits = Unit::count();
        $this->availableUnits = Unit::where('vacancy', 'available')->count();

        $occupiedUnits = Unit::where('vacancy', 'unavailable')->count();
        $this->occupancyRate = $this->totalUnits > 0 ? round(($occupiedUnits / $this->totalUnits) * 100) : 0;
    }

    public function updatedSearch()
    {
        $this->loadUnits();
    }

    public function updatedSelectedBuilding()
    {
        $this->loadUnits();
    }

    public function updatedSelectedZone()
    {
        $this->loadUnits();
    }

    public function updatedSelectedVacancy()
    {
        $this->loadUnits();
    }

    public function updatedSelectedType()
    {
        $this->loadUnits();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadUnits();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedBuilding = '';
        $this->selectedZone = '';
        $this->selectedVacancy = '';
        $this->selectedType = '';
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->loadUnits();
    }

    public function getVacancyColor($vacancy)
    {
        return match($vacancy) {
            'available' => 'text-green-400 bg-green-500',
            'unavailable' => 'text-red-400 bg-red-500',
            'pending' => 'text-yellow-400 bg-yellow-500',
            default => 'text-gray-400 bg-gray-500'
        };
    }

    public function getVacancyLabel($vacancy)
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

@volt
<div>
    <x-layouts.app :title="__('Units')">
        <div class="bg-gray-900 min-h-screen">
            <div class="flex flex-col gap-6 p-4 md:p-6">

                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-white">Units</h1>
                        <p class="text-sm text-gray-400 mt-1">Manage and view all units across buildings</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button variant="ghost" size="sm" wire:click="loadUnits" icon="arrow-path">
                            Refresh
                        </flux:button>
                        <flux:button variant="primary" size="sm" icon="plus">
                            Add Unit
                        </flux:button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                <p class="text-sm font-medium text-gray-400">Available Units</p>
                                <p class="text-2xl font-bold text-white mt-1">{{ $availableUnits }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-green-500 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-400">Occupancy Rate</p>
                                <p class="text-2xl font-bold text-white mt-1">{{ $occupancyRate }}%</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Controls -->
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <div class="flex flex-col gap-4">
                        <!-- Top Row: Search -->
                        <div class="flex flex-col lg:flex-row gap-4 items-center">
                            <div class="flex-1 w-full lg:w-auto">
                                <flux:field>
                                    <flux:input
                                        wire:model.live.debounce.300ms="search"
                                        placeholder="Search units, buildings, or addresses..."
                                        icon="magnifying-glass"
                                        class="w-full"
                                    />
                                </flux:field>
                            </div>
                        </div>

                        <!-- Second Row: Filters -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Building Filter -->
                            <flux:field>
                                <flux:select wire:model.live="selectedBuilding" placeholder="All Buildings">
                                    <option value="">All Buildings</option>
                                    @foreach($buildings as $building)
                                        <option value="{{ $building->id }}">{{ $building->name }} ({{ $building->zone->name }})</option>
                                    @endforeach
                                </flux:select>
                            </flux:field>

                            <!-- Zone Filter -->
                            <flux:field>
                                <flux:select wire:model.live="selectedZone" placeholder="All Zones">
                                    <option value="">All Zones</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </flux:select>
                            </flux:field>

                            <!-- Vacancy Filter -->
                            <flux:field>
                                <flux:select wire:model.live="selectedVacancy" placeholder="All Statuses">
                                    <option value="">All Statuses</option>
                                    <option value="available">Available</option>
                                    <option value="unavailable">Occupied</option>
                                    <option value="pending">Pending</option>
                                </flux:select>
                            </flux:field>

                            <!-- Type Filter -->
                            <flux:field>
                                <flux:select wire:model.live="selectedType" placeholder="All Types">
                                    <option value="">All Types</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        </div>

                        <!-- Third Row: Sort Controls -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 flex-wrap">
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
                                    variant="{{ $sortBy === 'building' ? 'primary' : 'ghost' }}"
                                    size="sm"
                                    wire:click="sortBy('building')"
                                >
                                    Building
                                    @if($sortBy === 'building')
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
                                    variant="{{ $sortBy === 'vacancy' ? 'primary' : 'ghost' }}"
                                    size="sm"
                                    wire:click="sortBy('vacancy')"
                                >
                                    Status
                                    @if($sortBy === 'vacancy')
                                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                                    @endif
                                </flux:button>
                            </div>

                            @if($search || $selectedBuilding || $selectedZone || $selectedVacancy || $selectedType || $sortBy !== 'name' || $sortDirection !== 'asc')
                                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                                    Clear All
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Units Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse($units as $unit)
                        @php
                            $vacancyColorClasses = $this->getVacancyColor($unit->vacancy);
                            $vacancyLabel = $this->getVacancyLabel($unit->vacancy);
                        @endphp

                        <div class="bg-gray-800 rounded-lg border border-gray-700 hover:border-green-500 transition-all duration-200 group cursor-pointer">
                            <a href="units/{{$unit->slug}}" class="block p-6">
                                <!-- Unit Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center group-hover:bg-opacity-30 transition-colors">
                                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                        </svg>
                                    </div>
                                    <span class="text-xs {{ $vacancyColorClasses }} bg-opacity-20 px-2 py-1 rounded-full">
                                        {{ $vacancyLabel }}
                                    </span>
                                </div>

                                <!-- Unit Info -->
                                <div class="space-y-2">
                                    <h3 class="text-lg font-semibold text-white group-hover:text-green-400 transition-colors">
                                        {{ $unit->name }}
                                    </h3>

                                    <p class="text-sm text-gray-400">
                                        {{ $unit->building->name }}
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        {{ $unit->building->zone->name }}
                                    </p>

                                    @if($unit->type)
                                        <p class="text-xs text-blue-400 bg-blue-500 bg-opacity-20 px-2 py-1 rounded-full inline-block">
                                            {{ $unit->type }}
                                        </p>
                                    @endif

                                    @if($unit->address)
                                        <p class="text-xs text-gray-500 flex items-center mt-2">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ $unit->address }}
                                            @if($unit->postcode)
                                                <span class="ml-1">{{ $unit->postcode }}</span>
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                <!-- Unit Actions -->
                                <div class="mt-4 pt-4 border-t border-gray-700">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center space-x-4">
                                            <span class="text-gray-400">
                                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Updated {{ $unit->updated_at->diffForHumans() }}
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-white mb-2">
                                    @if($search || $selectedBuilding || $selectedZone || $selectedVacancy || $selectedType)
                                        No units found
                                    @else
                                        No units yet
                                    @endif
                                </h3>
                                <p class="text-gray-400 mb-6">
                                    @if($search || $selectedBuilding || $selectedZone || $selectedVacancy || $selectedType)
                                        Try adjusting your search criteria or filters.
                                    @else
                                        Get started by adding your first unit.
                                    @endif
                                </p>
                                @if($search || $selectedBuilding || $selectedZone || $selectedVacancy || $selectedType)
                                    <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark">
                                        Clear Filters
                                    </flux:button>
                                @else
                                    <flux:button variant="primary" icon="plus">
                                        Add First Unit
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Loading States -->
                <div wire:loading.delay wire:target="search,selectedBuilding,selectedZone,selectedVacancy,selectedType,sortBy" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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
