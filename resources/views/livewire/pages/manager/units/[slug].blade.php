<?php
// resources/views/pages/units/[slug].blade.php

use App\Models\Unit;
use function Laravel\Folio\name;
use Livewire\Volt\Component;

name('units.show');

\Laravel\Folio\middleware(['auth', 'verified']);

new class extends Component
{
    public $unit;
    public $tenant;

    public function mount($slug)
    {
        $this->unit = Unit::where('slug', $slug)
            ->with(['building', 'building.zone', 'tenants'])
            ->firstOrFail();

        // Get the active tenant (assuming one tenant per unit)
        $this->tenant = $this->unit->tenants()
            ->where('status', 'active')
            ->first();
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

    public function getTenantStatusColor($status)
    {
        return match($status) {
            'active' => 'text-green-400 bg-green-500',
            'inactive' => 'text-yellow-400 bg-yellow-500',
            'terminated' => 'text-red-400 bg-red-500',
            default => 'text-gray-400 bg-gray-500'
        };
    }

    public function isLeaseExpiringSoon()
    {
        if (!$this->tenant || !$this->tenant->lease_end_date) {
            return false;
        }

        $today = now();
        $leaseEnd = \Carbon\Carbon::parse($this->tenant->lease_end_date);

        // Check if lease end is in the future and within 30 days
        return $leaseEnd->isFuture() && $today->diffInDays($leaseEnd) <= 30;
    }

    public function isRentOverdue()
    {
        if (!$this->tenant || !$this->tenant->rent_due_date) {
            return false;
        }

        return \Carbon\Carbon::parse($this->tenant->rent_due_date)->isPast();
    }
}

?>

@volt
<div class="bg-gray-900 min-h-screen">
    <x-layouts.app>
        <div class="flex flex-col gap-4 md:gap-8 p-4 md:p-6">

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                    <!-- Back Button -->
                    <a href="/manager/units"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-lg border border-gray-600 transition-all duration-200 w-fit">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Units
                    </a>

                    <!-- Unit Title -->
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $unit->name }}</h1>
                            <span class="text-xs {{ $this->getVacancyColor($unit->vacancy) }} bg-opacity-20 px-2 py-1 rounded-full">
                                {{ $this->getVacancyLabel($unit->vacancy) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-400 mt-1">
                            <a href="/manager/buildings/{{ $unit->building->slug }}" class="text-green-400 hover:text-green-300 transition-colors">
                                {{ $unit->building->name }}
                            </a>
                            • {{ $unit->building->zone->name }}
                            @if($unit->address)
                                • {{ $unit->address }}
                                @if($unit->postcode)
                                    {{ $unit->postcode }}
                                @endif
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3">
                    @if($unit->vacancy === 'available')
                        <flux:button variant="primary" size="sm" icon="user-plus">
                            Add Tenant
                        </flux:button>
                    @endif
                    <flux:button variant="ghost" size="sm" icon="pencil">
                        Edit Unit
                    </flux:button>
                </div>
            </div>

            <!-- Alert Messages -->
            @if($this->isLeaseExpiringSoon())
                <div class="bg-yellow-900 border border-yellow-700 text-yellow-100 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span class="font-medium">Lease expiring soon! </span>
                        <span>Tenant's lease expires on {{ \Carbon\Carbon::parse($tenant->lease_end_date)->format('M j, Y') }}</span>
                    </div>
                </div>
            @endif

            @if($this->isRentOverdue())
                <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">Rent overdue! </span>
                        <span>Payment was due {{ \Carbon\Carbon::parse($tenant->rent_due_date)->format('M j, Y') }}</span>
                    </div>
                </div>
            @endif

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm font-medium text-gray-400">Monthly Rent</p>
                            <p class="text-xl md:text-2xl font-bold text-white mt-1">
                                £{{ $tenant ? number_format($tenant->rent, 2) : '0.00' }}
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm font-medium text-gray-400">Lease Status</p>
                            <p class="text-lg md:text-xl font-bold text-white mt-1">
                                @if($tenant)
                                    {{ ucfirst($tenant->status) }}
                                @else
                                    No Tenant
                                @endif
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm font-medium text-gray-400">Days in Tenancy</p>
                            <p class="text-xl md:text-2xl font-bold text-white mt-1">
                                @if($tenant && $tenant->lease_start_date)
                                    {{ max(0, (int) \Carbon\Carbon::parse($tenant->lease_start_date)->diffInDays(now(), false)) }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0v10a2 2 0 002 2h2a2 2 0 002-2V7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm font-medium text-gray-400">Lease Remaining</p>
                            <p class="text-xl md:text-2xl font-bold text-white mt-1">
                                @if($tenant && $tenant->lease_end_date)
                                    @php
                                        $daysRemaining = (int) \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($tenant->lease_end_date), false);
                                    @endphp
                                    {{ $daysRemaining > 0 ? $daysRemaining . ' days' : 'Expired' }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-8 flex-1">
                <!-- Tenants Component -->
                <livewire:unit-tenants :unit="$unit" />

                <!-- Documents Component -->
                <livewire:unit-documents :unit="$unit" />
            </div>

            <!-- Unit Information -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Unit Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Unit Name</span>
                        <span class="text-sm font-medium text-white truncate ml-2">{{ $unit->name }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Building</span>
                        <a href="/manager/buildings/{{ $unit->building->slug }}" class="text-sm text-green-400 hover:text-green-300 transition-colors">
                            {{ $unit->building->name }}
                        </a>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Zone</span>
                        <span class="text-sm text-white">{{ $unit->building->zone->name }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Type</span>
                        <span class="text-sm text-white">{{ $unit->type ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Status</span>
                        <span class="text-sm {{ $this->getVacancyColor($unit->vacancy) }}">{{ $this->getVacancyLabel($unit->vacancy) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Address</span>
                        <span class="text-sm text-white">{{ $unit->address ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Postcode</span>
                        <span class="text-sm text-white">{{ $unit->postcode ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-400">Last Updated</span>
                        <span class="text-sm text-white">{{ $unit->updated_at->format('M j, Y') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </x-layouts.app>
</div>
@endvolt
