<?php
// resources/views/livewire/unit-tenants.blade.php

use App\Models\Unit;
use App\Models\Tenant;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Validator;

new class extends Component
{
    public Unit $unit;
    public $tenants = [];

    // Form fields
    public $title = '';
    public $firstName = '';
    public $lastName = '';
    public $email = '';
    public $phone = '';
    public $rent = '';
    public $leaseStartDate = '';
    public $leaseEndDate = '';
    public $status = 'active';
    public $rentDueDate = '';

    public function mount(Unit $unit)
    {
        $this->unit = $unit;
        $this->loadTenants();
    }

    public function loadTenants()
    {
        $this->tenants = $this->unit->tenants()->latest()->get();
    }

    public function addTenant()
    {
        $this->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:tenants,email',
            'phone' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:10',
            'rent' => 'required|numeric|min:0|max:999999.99',
            'leaseStartDate' => 'nullable|date',
            'leaseEndDate' => 'nullable|date|after_or_equal:leaseStartDate',
            'status' => 'required|in:active,inactive,terminated',
            'rentDueDate' => 'nullable|date',
        ]);

        try {
            // Check if unit is available for new tenant
            $activeTenants = $this->unit->tenants()->where('status', 'active')->count();
            if ($activeTenants > 0 && $this->status === 'active') {
                session()->flash('error', 'This unit already has an active tenant. Please terminate the existing lease first.');
                return;
            }

            $tenant = Tenant::create([
                'title' => $this->title,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'phone' => $this->phone,
                'unit_id' => $this->unit->id,
                'rent' => $this->rent,
                'lease_start_date' => $this->leaseStartDate,
                'lease_end_date' => $this->leaseEndDate,
                'status' => $this->status,
                'rent_due_date' => $this->rentDueDate,
            ]);

            // Update unit vacancy status if tenant is active
            if ($this->status === 'active') {
                $this->unit->update(['vacancy' => 'unavailable']);
            }

            $this->resetForm();
            $this->loadTenants();

            session()->flash('message', 'Tenant "' . $this->firstName . ' ' . $this->lastName . '" added successfully!');

            $this->js('setTimeout(() => { $flux.modal("addTenant").close(); }, 100);');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add tenant. Please try again.');
            \Log::error('Tenant creation failed: ' . $e->getMessage());
        }
    }

    public function updateTenantStatus($tenantId, $newStatus)
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $oldStatus = $tenant->status;
            $tenant->status = $newStatus;
            $tenant->save();

            // Update unit vacancy based on active tenants
            $activeTenants = $this->unit->tenants()->where('status', 'active')->count();
            $this->unit->update([
                'vacancy' => $activeTenants > 0 ? 'unavailable' : 'available'
            ]);

            $this->loadTenants();

            session()->flash('message', "Tenant status updated from {$oldStatus} to {$newStatus}.");

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update tenant status.');
        }
    }

    public function resetForm()
    {
        $this->title = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->email = '';
        $this->phone = '';
        $this->rent = '';
        $this->leaseStartDate = '';
        $this->leaseEndDate = '';
        $this->status = 'active';
        $this->rentDueDate = '';
    }

    public function getTenantStatusColor($status)
    {
        return match($status) {
            'active' => 'bg-green-500 text-green-400',
            'inactive' => 'bg-yellow-500 text-yellow-400',
            'terminated' => 'bg-red-500 text-red-400',
            default => 'bg-gray-500 text-gray-400'
        };
    }

    public function isLeaseExpiringSoon($leaseEndDate)
    {
        if (!$leaseEndDate) return false;

        $today = now();
        $leaseEnd = \Carbon\Carbon::parse($leaseEndDate);

        return $leaseEnd->diffInDays($today) <= 30 && $leaseEnd->isFuture();
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
                <h2 class="text-lg font-semibold text-white">Tenants</h2>
                <flux:modal.trigger name="addTenant">
                    <flux:button variant="ghost" size="sm" icon="plus">
                        <span class="hidden sm:inline">Add Tenant</span>
                        <span class="sm:hidden">Add</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Tenants List or Empty State -->
        @if($tenants && count($tenants) > 0)
            <div class="divide-y divide-gray-700">
                @foreach($tenants as $tenant)
                    <a href="/manager/tenants/{{$tenant->id}}">
                    <div class="p-4 hover:bg-gray-700 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <!-- Tenant Avatar -->
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center font-semibold text-white text-sm">
                                    {{ strtoupper(substr($tenant->first_name, 0, 1) . substr($tenant->last_name, 0, 1)) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-medium text-white">
                                            {{ $tenant->title ? $tenant->title . ' ' : '' }}{{ $tenant->first_name }} {{ $tenant->last_name }}
                                        </h4>
                                        @if($this->isLeaseExpiringSoon($tenant->lease_end_date))
                                            <span class="text-xs bg-yellow-500 bg-opacity-20 text-yellow-400 px-2 py-1 rounded-full">
                                                Lease expiring soon
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400">
                                        {{ $tenant->email }} • £{{ number_format($tenant->rent, 2) }}/month
                                        @if($tenant->lease_start_date)
                                            • Since {{ \Carbon\Carbon::parse($tenant->lease_start_date)->format('M Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <!-- Status Badge -->
                                <span class="text-xs {{ $this->getTenantStatusColor($tenant->status) }} bg-opacity-20 px-2 py-1 rounded">
                                    {{ ucfirst($tenant->status) }}
                                </span>

                                <!-- Status Change Dropdown -->
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @if($tenant->status !== 'active')
                                            <flux:menu.item wire:click="updateTenantStatus({{ $tenant->id }}, 'active')" icon="check-circle">
                                                Set Active
                                            </flux:menu.item>
                                        @endif
                                        @if($tenant->status !== 'inactive')
                                            <flux:menu.item wire:click="updateTenantStatus({{ $tenant->id }}, 'inactive')" icon="pause-circle">
                                                Set Inactive
                                            </flux:menu.item>
                                        @endif
                                        @if($tenant->status !== 'terminated')
                                            <flux:menu.item wire:click="updateTenantStatus({{ $tenant->id }}, 'terminated')" icon="x-circle">
                                                Terminate Lease
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>

                        <!-- Lease Details -->
                        @if($tenant->lease_start_date || $tenant->lease_end_date || $tenant->rent_due_date)
                            <div class="mt-3 pt-3 border-t border-gray-600">
                                <div class="grid grid-cols-3 gap-3 text-xs">
                                    @if($tenant->lease_start_date)
                                        <div>
                                            <span class="text-gray-500">Start:</span>
                                            <span class="text-white">{{ \Carbon\Carbon::parse($tenant->lease_start_date)->format('M j, Y') }}</span>
                                        </div>
                                    @endif
                                    @if($tenant->lease_end_date)
                                        <div>
                                            <span class="text-gray-500">End:</span>
                                            <span class="text-white">{{ \Carbon\Carbon::parse($tenant->lease_end_date)->format('M j, Y') }}</span>
                                        </div>
                                    @endif
                                    @if($tenant->rent_due_date)
                                        <div>
                                            <span class="text-gray-500">Next Due:</span>
                                            <span class="text-white">{{ \Carbon\Carbon::parse($tenant->rent_due_date)->format('M j, Y') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    </a>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="p-8 md:p-12 text-center">
                <div class="w-12 h-12 md:w-16 md:h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-white mb-2">No tenants yet</h3>
                <p class="text-gray-400 mb-6 text-sm md:text-base">Add your first tenant to this unit.</p>
                <flux:modal.trigger name="addTenant">
                    <flux:button variant="primary" size="sm" icon="plus">
                        Add First Tenant
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </div>

    <!-- Add Tenant Modal -->
    <flux:modal name="addTenant" class="md:w-[600px]">
        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                        <flux:icon.user-plus class="w-5 h-5 text-white" />
                    </div>
                    <flux:heading size="lg">Add New Tenant</flux:heading>
                </div>
                <flux:subheading>
                    Add a new tenant to {{ $unit->name }}. Fill out their personal and lease information.
                </flux:subheading>
            </div>

            <!-- Form Section -->
            <form wire:submit="addTenant" class="space-y-6">
                <!-- Personal Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-white border-b border-gray-700 pb-2">Personal Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Title -->
                        <flux:field>
                            <flux:label>Title</flux:label>
                            <flux:select wire:model="title" placeholder="Select title">
                                <option value="">No title</option>
                                <option value="Mr">Mr</option>
                                <option value="Mrs">Mrs</option>
                                <option value="Ms">Ms</option>
                                <option value="Dr">Dr</option>
                                <option value="Prof">Prof</option>
                            </flux:select>
                            <flux:error name="title" />
                        </flux:field>

                        <!-- First Name -->
                        <flux:field>
                            <flux:label>First Name *</flux:label>
                            <flux:input
                                wire:model="firstName"
                                placeholder="John"
                                required
                            />
                            <flux:error name="firstName" />
                        </flux:field>

                        <!-- Last Name -->
                        <flux:field>
                            <flux:label>Last Name *</flux:label>
                            <flux:input
                                wire:model="lastName"
                                placeholder="Smith"
                                required
                            />
                            <flux:error name="lastName" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Email -->
                        <flux:field>
                            <flux:label>Email Address *</flux:label>
                            <flux:input
                                wire:model="email"
                                type="email"
                                placeholder="john.smith@example.com"
                                required
                            />
                            <flux:error name="email" />
                        </flux:field>

                        <!-- Phone -->
                        <flux:field>
                            <flux:label>Phone Number</flux:label>
                            <flux:input
                                wire:model="phone"
                                type="tel"
                                placeholder="+44 7123 456789"
                            />
                            <flux:error name="phone" />
                        </flux:field>
                    </div>
                </div>

                <!-- Lease Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-white border-b border-gray-700 pb-2">Lease Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Monthly Rent -->
                        <flux:field>
                            <flux:label>Monthly Rent (£) *</flux:label>
                            <flux:input
                                wire:model="rent"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="1200.00"
                                required
                            />
                            <flux:error name="rent" />
                        </flux:field>

                        <!-- Status -->
                        <flux:field>
                            <flux:label>Status *</flux:label>
                            <flux:select wire:model="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="terminated">Terminated</option>
                            </flux:select>
                            <flux:error name="status" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Lease Start Date -->
                        <flux:field>
                            <flux:label>Lease Start Date</flux:label>
                            <flux:input
                                wire:model="leaseStartDate"
                                type="date"
                            />
                            <flux:error name="leaseStartDate" />
                        </flux:field>

                        <!-- Lease End Date -->
                        <flux:field>
                            <flux:label>Lease End Date</flux:label>
                            <flux:input
                                wire:model="leaseEndDate"
                                type="date"
                            />
                            <flux:error name="leaseEndDate" />
                        </flux:field>

                        <!-- Rent Due Date -->
                        <flux:field>
                            <flux:label>Next Rent Due Date</flux:label>
                            <flux:input
                                wire:model="rentDueDate"
                                type="date"
                            />
                            <flux:error name="rentDueDate" />
                        </flux:field>
                    </div>
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
                        wire:target="addTenant"
                    >
                        <span wire:loading.remove wire:target="addTenant">Add Tenant</span>
                        <span wire:loading wire:target="addTenant">Adding...</span>
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
