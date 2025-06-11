<?php
// resources/views/pages/manager/tenants/index.blade.php

use function Laravel\Folio\name;
use App\Models\Tenant;
use App\Models\Building;
use App\Models\Zone;
use App\Models\User;
use Livewire\Volt\Component;

name('tenants.index');

\Laravel\Folio\middleware(['auth', 'verified']);

new class extends Component
{
    public $search = '';
    public $sortBy = 'first_name';
    public $sortDirection = 'asc';
    public $selectedBuilding = '';
    public $selectedZone = '';
    public $selectedStatus = '';
    public $selectedUserStatus = '';
    public $perPage = 12;

    public $tenants;
    public $buildings;
    public $zones;
    public $totalTenants;
    public $activeTenants;
    public $averageRent;

    public function mount()
    {
        $this->buildings = Building::with('zone')->orderBy('name')->get();
        $this->zones = Zone::orderBy('name')->get();
        $this->loadTenants();
        $this->loadStats();
    }

    public function loadTenants()
    {
        $query = Tenant::with(['unit', 'unit.building', 'unit.building.zone', 'user']);

        // Search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhereHas('unit', function($uq) {
                        $uq->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('unit.building', function($bq) {
                        $bq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Building filter
        if ($this->selectedBuilding) {
            $query->whereHas('unit', function($uq) {
                $uq->where('building_id', $this->selectedBuilding);
            });
        }

        // Zone filter
        if ($this->selectedZone) {
            $query->whereHas('unit.building', function($bq) {
                $bq->where('zone_id', $this->selectedZone);
            });
        }

        // Status filter
        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        // User Status filter
        if ($this->selectedUserStatus) {
            if ($this->selectedUserStatus === 'has_user') {
                $query->whereNotNull('user_id');
            } elseif ($this->selectedUserStatus === 'no_user') {
                $query->whereNull('user_id');
            }
        }

        // Sorting
        if ($this->sortBy === 'building') {
            $query->join('units', 'tenants.unit_id', '=', 'units.id')
                ->join('buildings', 'units.building_id', '=', 'buildings.id')
                ->orderBy('buildings.name', $this->sortDirection)
                ->select('tenants.*');
        } elseif ($this->sortBy === 'zone') {
            $query->join('units', 'tenants.unit_id', '=', 'units.id')
                ->join('buildings', 'units.building_id', '=', 'buildings.id')
                ->join('zones', 'buildings.zone_id', '=', 'zones.id')
                ->orderBy('zones.name', $this->sortDirection)
                ->select('tenants.*');
        } elseif ($this->sortBy === 'full_name') {
            $query->orderBy('first_name', $this->sortDirection)
                ->orderBy('last_name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $this->tenants = $query->take($this->perPage)->get();
    }

    public function loadStats()
    {
        $this->totalTenants = Tenant::count();
        $this->activeTenants = Tenant::where('status', 'active')->count();

        $avgRent = Tenant::where('status', 'active')->avg('rent');
        $this->averageRent = $avgRent ? round($avgRent, 2) : 0;
    }

    public function updatedSearch()
    {
        $this->loadTenants();
    }

    public function updatedSelectedBuilding()
    {
        $this->loadTenants();
    }

    public function updatedSelectedZone()
    {
        $this->loadTenants();
    }

    public function updatedSelectedStatus()
    {
        $this->loadTenants();
    }

    public function updatedSelectedUserStatus()
    {
        $this->loadTenants();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadTenants();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedBuilding = '';
        $this->selectedZone = '';
        $this->selectedStatus = '';
        $this->selectedUserStatus = '';
        $this->sortBy = 'first_name';
        $this->sortDirection = 'asc';
        $this->loadTenants();
    }

    public function getTenantStatusColor($status)
    {
        return match($status) {
            'active' => 'text-green-400 bg-green-500',
            'inactive' => 'text-yellow-400 bg-yellow-500',
            'terminated' => 'text-red-400 bg-red-500',
            default => 'text-gray-400 bg-gray-500'
        };
    }

    public function getTenantStatusLabel($status)
    {
        return match($status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'terminated' => 'Terminated',
            default => 'Unknown'
        };
    }

    public function isLeaseExpiringSoon($leaseEndDate)
    {
        if (!$leaseEndDate) return false;

        $today = now();
        $leaseEnd = \Carbon\Carbon::parse($leaseEndDate);

        return $leaseEnd->isFuture() && $today->diffInDays($leaseEnd) <= 30;
    }
}

?>

@volt
<div>
    <x-layouts.app :title="__('Tenants')">
        <div class="bg-gray-900 min-h-screen">
            <div class="flex flex-col gap-6 p-4 md:p-6">

                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-white">Tenants</h1>
                        <p class="text-sm text-gray-400 mt-1">Manage and view all tenants across properties</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button variant="ghost" size="sm" wire:click="loadTenants" icon="arrow-path">
                            Refresh
                        </flux:button>
                        <flux:button variant="primary" size="sm" icon="plus">
                            Add Tenant
                        </flux:button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-green-500 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-400">Total Tenants</p>
                                <p class="text-2xl font-bold text-white mt-1">{{ $totalTenants }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-green-500 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-400">Active Tenants</p>
                                <p class="text-2xl font-bold text-white mt-1">{{ $activeTenants }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-green-500 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-400">Average Rent</p>
                                <p class="text-2xl font-bold text-white mt-1">£{{ number_format($averageRent, 2) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
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
                                        placeholder="Search tenants, units, buildings..."
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

                            <!-- Status Filter -->
                            <flux:field>
                                <flux:select wire:model.live="selectedStatus" placeholder="All Statuses">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="terminated">Terminated</option>
                                </flux:select>
                            </flux:field>

                            <!-- User Status Filter -->
                            <flux:field>
                                <flux:select wire:model.live="selectedUserStatus" placeholder="All User Types">
                                    <option value="">All User Types</option>
                                    <option value="has_user">Has Portal Access</option>
                                    <option value="no_user">No Portal Access</option>
                                </flux:select>
                            </flux:field>
                        </div>

                        <!-- Third Row: Sort Controls -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 flex-wrap">
                                <flux:button
                                    variant="{{ $sortBy === 'full_name' ? 'primary' : 'ghost' }}"
                                    size="sm"
                                    wire:click="sortBy('full_name')"
                                >
                                    Name
                                    @if($sortBy === 'full_name')
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
                                    variant="{{ $sortBy === 'status' ? 'primary' : 'ghost' }}"
                                    size="sm"
                                    wire:click="sortBy('status')"
                                >
                                    Status
                                    @if($sortBy === 'status')
                                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                                    @endif
                                </flux:button>

                                <flux:button
                                    variant="{{ $sortBy === 'rent' ? 'primary' : 'ghost' }}"
                                    size="sm"
                                    wire:click="sortBy('rent')"
                                >
                                    Rent
                                    @if($sortBy === 'rent')
                                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                                    @endif
                                </flux:button>
                            </div>

                            @if($search || $selectedBuilding || $selectedZone || $selectedStatus || $selectedUserStatus || $sortBy !== 'first_name' || $sortDirection !== 'asc')
                                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                                    Clear All
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tenants Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse($tenants as $tenant)
                        @php
                            $statusColorClasses = $this->getTenantStatusColor($tenant->status);
                            $statusLabel = $this->getTenantStatusLabel($tenant->status);
                        @endphp

                        <div class="bg-gray-800 rounded-lg border border-gray-700 hover:border-green-500 transition-all duration-200 group cursor-pointer">
                            <a href="/manager/tenants/{{ $tenant->id }}" class="block p-6">
                                <!-- Tenant Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center font-semibold text-white text-sm group-hover:bg-green-400 transition-colors">
                                        {{ strtoupper(substr($tenant->first_name, 0, 1) . substr($tenant->last_name, 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs {{ $statusColorClasses }} bg-opacity-20 px-2 py-1 rounded-full">
                                            {{ $statusLabel }}
                                        </span>
                                        @if($tenant->user)
                                            <span class="text-xs bg-blue-500 bg-opacity-20 text-blue-400 px-2 py-1 rounded-full">
                                                Portal Access
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Tenant Info -->
                                <div class="space-y-2">
                                    <h3 class="text-lg font-semibold text-white group-hover:text-green-400 transition-colors">
                                        {{ $tenant->title ? $tenant->title . ' ' : '' }}{{ $tenant->first_name }} {{ $tenant->last_name }}
                                    </h3>

                                    <p class="text-sm text-gray-400">
                                        {{ $tenant->email }}
                                    </p>

                                    @if($tenant->unit)
                                        <p class="text-xs text-gray-500 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m-2 0h2M7 7h10M7 10h10M7 13h7"/>
                                            </svg>
                                            {{ $tenant->unit->name }} - {{ $tenant->unit->building->name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $tenant->unit->building->zone->name }}
                                        </p>
                                    @endif

                                    @if($this->isLeaseExpiringSoon($tenant->lease_end_date))
                                        <p class="text-xs bg-yellow-500 bg-opacity-20 text-yellow-400 px-2 py-1 rounded-full inline-block">
                                            Lease expiring soon
                                        </p>
                                    @endif
                                </div>

                                <!-- Tenant Stats -->
                                <div class="mt-4 pt-4 border-t border-gray-700">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center space-x-4">
                                            <span class="text-gray-400">
                                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                </svg>
                                                £{{ number_format($tenant->rent, 2) }}/month
                                            </span>

                                            @if($tenant->phone)
                                                <span class="text-gray-400">
                                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                    {{ $tenant->phone }}
                                                </span>
                                            @endif
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-white mb-2">
                                    @if($search || $selectedBuilding || $selectedZone || $selectedStatus || $selectedUserStatus)
                                        No tenants found
                                    @else
                                        No tenants yet
                                    @endif
                                </h3>
                                <p class="text-gray-400 mb-6">
                                    @if($search || $selectedBuilding || $selectedZone || $selectedStatus || $selectedUserStatus)
                                        Try adjusting your search criteria or filters.
                                    @else
                                        Get started by adding your first tenant.
                                    @endif
                                </p>
                                @if($search || $selectedBuilding || $selectedZone || $selectedStatus || $selectedUserStatus)
                                    <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark">
                                        Clear Filters
                                    </flux:button>
                                @else
                                    <flux:button variant="primary" icon="plus">
                                        Add First Tenant
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Loading States -->
                <div wire:loading.delay wire:target="search,selectedBuilding,selectedZone,selectedStatus,selectedUserStatus,sortBy" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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
