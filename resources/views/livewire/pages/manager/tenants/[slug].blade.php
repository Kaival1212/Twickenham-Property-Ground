<?php
// resources/views/pages/manager/tenants/[slug].blade.php

use App\Models\Tenant;
use App\Models\User;
use function Laravel\Folio\name;
use Livewire\Volt\Component;

name('tenants.show');

\Laravel\Folio\middleware(['auth', 'verified']);

new class extends Component
{
    public $tenant;
    public $user;

    public function mount($slug)
    {
        $this->tenant = Tenant::where('id', $slug)
            ->with(['unit', 'unit.building', 'unit.building.zone', 'user'])
            ->firstOrFail();

        $this->user = $this->tenant->user;
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

    public function isLeaseExpiringSoon()
    {
        if (!$this->tenant->lease_end_date) {
            return false;
        }

        $today = now();
        $leaseEnd = \Carbon\Carbon::parse($this->tenant->lease_end_date);

        return $leaseEnd->isFuture() && $today->diffInDays($leaseEnd) <= 30;
    }

    public function isRentOverdue()
    {
        if (!$this->tenant->rent_due_date) {
            return false;
        }

        return \Carbon\Carbon::parse($this->tenant->rent_due_date)->isPast();
    }

    public function hasPortalAccess()
    {
        return $this->user !== null;
    }

    public function createPortalAccess()
    {
        if ($this->user) {
            session()->flash('error', 'This tenant already has portal access.');
            return;
        }

        try {
            // Generate a random password
            $password = \Str::random(12);

            $user = User::create([
                'name' => $this->tenant->first_name . ' ' . $this->tenant->last_name,
                'email' => $this->tenant->email,
                'tenant_id' => $this->tenant->id,
                'first_time_password' => $password,
                'password' => bcrypt($password),
                'role' => 'tenant',
            ]);

            $this->user = $user;
            $this->tenant->load('user'); // Reload the relationship

            session()->flash('message', 'Portal access created successfully! Temporary password: ' . $password);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create portal access. Please try again.');
            \Log::error('Portal access creation failed: ' . $e->getMessage());
        }
    }

    public function removePortalAccess()
    {
        if (!$this->user) {
            session()->flash('error', 'This tenant does not have portal access.');
            return;
        }

        try {
            $this->user->delete();
            $this->user = null;
            $this->tenant->load('user'); // Reload the relationship

            session()->flash('message', 'Portal access removed successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove portal access. Please try again.');
            \Log::error('Portal access removal failed: ' . $e->getMessage());
        }
    }
}

?>

@volt
<div class="bg-gray-900 min-h-screen">
    <x-layouts.app>
        <div class="flex flex-col gap-4 md:gap-8 p-4 md:p-6">

            <!-- Flash Messages -->
            @if (session('message'))
                <div class="bg-green-500 bg-opacity-20 border border-green-500 text-green-400 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="font-medium">{{ session('message') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-400 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                    <!-- Back Button -->
                    <a href="/manager/tenants"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-lg border border-gray-600 transition-all duration-200 w-fit">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Tenants
                    </a>

                    <!-- Tenant Title -->
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl md:text-3xl font-bold text-white">
                                {{ $tenant->title ? $tenant->title . ' ' : '' }}{{ $tenant->first_name }} {{ $tenant->last_name }}
                            </h1>
                            <span class="text-xs {{ $this->getTenantStatusColor($tenant->status) }} bg-opacity-20 px-2 py-1 rounded-full">
                                {{ $this->getTenantStatusLabel($tenant->status) }}
                            </span>
                            @if($this->hasPortalAccess())
                                <span class="text-xs bg-blue-500 bg-opacity-20 text-blue-400 px-2 py-1 rounded-full">
                                    Portal Access
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-400 mt-1">
                            {{ $tenant->email }}
                            @if($tenant->unit)
                                • <a href="/manager/units/{{ $tenant->unit->slug }}" class="text-green-400 hover:text-green-300 transition-colors">
                                    {{ $tenant->unit->name }}
                                </a>
                                • <a href="/manager/buildings/{{ $tenant->unit->building->slug }}" class="text-green-400 hover:text-green-300 transition-colors">
                                    {{ $tenant->unit->building->name }}
                                </a>
                                • {{ $tenant->unit->building->zone->name }}
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3">
                    @if($this->hasPortalAccess())
                        <flux:button variant="ghost" size="sm" icon="key" wire:click="removePortalAccess" wire:confirm="Are you sure you want to remove portal access for this tenant?">
                            Remove Portal Access
                        </flux:button>
                    @else
                        <flux:button variant="primary" size="sm" icon="key" wire:click="createPortalAccess">
                            Create Portal Access
                        </flux:button>
                    @endif
                    <flux:button variant="ghost" size="sm" icon="pencil">
                        Edit Tenant
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
                        <span>Lease expires on {{ \Carbon\Carbon::parse($tenant->lease_end_date)->format('M j, Y') }}</span>
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
                                £{{ number_format($tenant->rent, 2) }}
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
                                {{ ucfirst($tenant->status) }}
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
                                @if($tenant->lease_start_date)
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
                                @if($tenant->lease_end_date)
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
                <!-- Tenant Information -->
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-white mb-6">Tenant Information</h3>

                    <div class="space-y-6">
                        <!-- Personal Information -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-3">Personal Details</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Full Name</p>
                                    <p class="text-sm text-white">
                                        {{ $tenant->title ? $tenant->title . ' ' : '' }}{{ $tenant->first_name }} {{ $tenant->last_name }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Email Address</p>
                                    <p class="text-sm text-white">{{ $tenant->email }}</p>
                                </div>
                                @if($tenant->phone)
                                    <div>
                                        <p class="text-xs text-gray-500">Phone Number</p>
                                        <p class="text-sm text-white">{{ $tenant->phone }}</p>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-xs text-gray-500">Status</p>
                                    <span class="text-sm {{ $this->getTenantStatusColor($tenant->status) }}">
                                        {{ $this->getTenantStatusLabel($tenant->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Lease Information -->
                        <div class="border-t border-gray-700 pt-6">
                            <h4 class="text-sm font-medium text-gray-400 mb-3">Lease Details</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Monthly Rent</p>
                                    <p class="text-sm text-white">£{{ number_format($tenant->rent, 2) }}</p>
                                </div>
                                @if($tenant->lease_start_date)
                                    <div>
                                        <p class="text-xs text-gray-500">Lease Start Date</p>
                                        <p class="text-sm text-white">{{ \Carbon\Carbon::parse($tenant->lease_start_date)->format('M j, Y') }}</p>
                                    </div>
                                @endif
                                @if($tenant->lease_end_date)
                                    <div>
                                        <p class="text-xs text-gray-500">Lease End Date</p>
                                        <p class="text-sm text-white">{{ \Carbon\Carbon::parse($tenant->lease_end_date)->format('M j, Y') }}</p>
                                    </div>
                                @endif
                                @if($tenant->rent_due_date)
                                    <div>
                                        <p class="text-xs text-gray-500">Next Rent Due</p>
                                        <p class="text-sm text-white">{{ \Carbon\Carbon::parse($tenant->rent_due_date)->format('M j, Y') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Property Information -->
                        @if($tenant->unit)
                            <div class="border-t border-gray-700 pt-6">
                                <h4 class="text-sm font-medium text-gray-400 mb-3">Property Details</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500">Unit</p>
                                        <a href="/manager/units/{{ $tenant->unit->slug }}" class="text-sm text-green-400 hover:text-green-300 transition-colors">
                                            {{ $tenant->unit->name }}
                                        </a>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Building</p>
                                        <a href="/manager/buildings/{{ $tenant->unit->building->slug }}" class="text-sm text-green-400 hover:text-green-300 transition-colors">
                                            {{ $tenant->unit->building->name }}
                                        </a>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Zone</p>
                                        <p class="text-sm text-white">{{ $tenant->unit->building->zone->name }}</p>
                                    </div>
                                    @if($tenant->unit->type)
                                        <div>
                                            <p class="text-xs text-gray-500">Unit Type</p>
                                            <p class="text-sm text-white">{{ $tenant->unit->type }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Portal Access & User Information -->
                <div class="space-y-4 md:space-y-6">
                    <!-- Portal Access Information -->
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Portal Access</h3>

                        @if($this->hasPortalAccess())
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 p-4 bg-blue-500 bg-opacity-10 rounded-lg border border-blue-500 border-opacity-20">
                                    <div class="w-10 h-10 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2m-2-2a2 2 0 00-2 2m2-2V5a2 2 0 00-2-2H9a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2V7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-white">Portal Access Active</h4>
                                        <p class="text-xs text-gray-400">Tenant can access the online portal</p>
                                    </div>
                                </div>

                                <!-- User Details -->
                                <div class="space-y-3">
                                    <h5 class="text-sm font-medium text-gray-400">User Account Details</h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-gray-500">Account Name</p>
                                            <p class="text-sm text-white">{{ $user->name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Email</p>
                                            <p class="text-sm text-white">{{ $user->email }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Role</p>
                                            <span class="text-sm bg-gray-500 bg-opacity-20 text-gray-400 px-2 py-1 rounded-full">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Account Created</p>
                                            <p class="text-sm text-white">{{ $user->created_at->format('M j, Y') }}</p>
                                        </div>
                                        @if($user->email_verified_at)
                                            <div>
                                                <p class="text-xs text-gray-500">Email Verified</p>
                                                <p class="text-sm text-green-400">{{ \Carbon\Carbon::parse($user->email_verified_at)->format('M j, Y') }}</p>
                                            </div>
                                        @else
                                            <div>
                                                <p class="text-xs text-gray-500">Email Status</p>
                                                <p class="text-sm text-yellow-400">Not verified</p>
                                            </div>
                                        @endif
                                        @if($user->first_time_password)
                                            <div class="col-span-full">
                                                <p class="text-xs text-gray-500">First-time Password</p>
                                                <p class="text-sm font-mono bg-gray-700 px-2 py-1 rounded text-white">{{ $user->first_time_password }}</p>
                                                <p class="text-xs text-yellow-400 mt-1">Provide this password to the tenant for first login</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Portal Actions -->
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" icon="arrow-path">
                                        Reset Password
                                    </flux:button>
                                    <flux:button variant="ghost" size="sm" icon="envelope">
                                        Send Welcome Email
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-medium text-white mb-2">No Portal Access</h4>
                                <p class="text-gray-400 mb-4">This tenant cannot access the online portal yet.</p>
                                <flux:button variant="primary" wire:click="createPortalAccess" icon="key">
                                    Create Portal Access
                                </flux:button>
                            </div>
                        @endif
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Recent Activity</h3>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3 text-sm">
                                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                <span class="text-gray-300">Tenant information updated</span>
                                <span class="text-gray-500">2 days ago</span>
                            </div>
                            @if($tenant->status === 'active')
                                <div class="flex items-center space-x-3 text-sm">
                                    <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                                    <span class="text-gray-300">Rent payment received</span>
                                    <span class="text-gray-500">5 days ago</span>
                                </div>
                            @endif
                            <div class="flex items-center space-x-3 text-sm">
                                <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                <span class="text-gray-300">Lease agreement signed</span>
                                <span class="text-gray-500">1 month ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tenant Summary Information -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Summary Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Tenant ID</span>
                        <span class="text-sm font-medium text-white">#{{ $tenant->id }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Status</span>
                        <span class="text-sm {{ $this->getTenantStatusColor($tenant->status) }}">{{ $this->getTenantStatusLabel($tenant->status) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Portal Access</span>
                        <span class="text-sm text-white">{{ $this->hasPortalAccess() ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Monthly Rent</span>
                        <span class="text-sm text-white">£{{ number_format($tenant->rent, 2) }}</span>
                    </div>
                    @if($tenant->unit)
                        <div class="flex justify-between items-center py-2 border-b border-gray-700">
                            <span class="text-sm text-gray-400">Unit</span>
                            <a href="/manager/units/{{ $tenant->unit->slug }}" class="text-sm text-green-400 hover:text-green-300 transition-colors">
                                {{ $tenant->unit->name }}
                            </a>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-700">
                            <span class="text-sm text-gray-400">Building</span>
                            <a href="/manager/buildings/{{ $tenant->unit->building->slug }}" class="text-sm text-green-400 hover:text-green-300 transition-colors">
                                {{ $tenant->unit->building->name }}
                            </a>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-700">
                            <span class="text-sm text-gray-400">Zone</span>
                            <span class="text-sm text-white">{{ $tenant->unit->building->zone->name }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-400">Added</span>
                        <span class="text-sm text-white">{{ $tenant->created_at->format('M j, Y') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </x-layouts.app>
</div>
@endvolt
