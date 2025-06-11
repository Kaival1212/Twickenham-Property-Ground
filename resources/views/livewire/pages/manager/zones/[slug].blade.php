<?php

use App\Models\Zone;
\Laravel\Folio\middleware(['auth', 'verified']);
use Livewire\Volt\Component;

new class extends Component
{
    public $zone;

    public function mount($slug)
    {
        $this->zone = Zone::where('slug', $slug)->firstOrFail();
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
                    <a href="{{ route('zones.index') }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-lg border border-gray-600 transition-all duration-200 w-fit">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Zones
                    </a>

                    <!-- Zone Title -->
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $zone->name }}</h1>
                        <p class="text-sm text-gray-400 mt-1">Zone management and overview</p>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm font-medium text-gray-400">Total Buildings</p>
                            <p class="text-xl md:text-2xl font-bold text-white mt-1">{{ $zone->buildings()->count() }}</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m-2 0h2M7 7h10M7 10h10M7 13h7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm font-medium text-gray-400">Total Documents</p>
                            <p class="text-xl md:text-2xl font-bold text-white mt-1">{{ $zone->documents()->count() }}</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm font-medium text-gray-400">Total Units</p>
                            <p class="text-xl md:text-2xl font-bold text-white mt-1">{{ $zone->buildings()->count() * 10 }}</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm font-medium text-gray-400">Avg Occupancy</p>
                            <p class="text-xl md:text-2xl font-bold text-white mt-1">94%</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-8 flex-1">
{{--                <!-- buildings Component -->--}}
                <livewire:zone-buildings :zone="$zone" />

{{--                <!-- Documents Component -->--}}
                <livewire:zone-documents :zone="$zone" />
            </div>

            <!-- Zone Information -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Zone Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Zone Name</span>
                        <span class="text-sm font-medium text-white truncate ml-2">{{ $zone->name }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Slug</span>
                        <span class="text-xs font-mono text-white bg-gray-700 px-2 py-1 rounded truncate ml-2">{{ $zone->slug }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700">
                        <span class="text-sm text-gray-400">Buildings</span>
                        <span class="text-sm text-white">{{ $zone->buildings()->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-400">Documents</span>
                        <span class="text-sm text-white">{{ $zone->documents()->count() }}</span>
                    </div>
                </div>
            </div>

        </div>
    </x-layouts.app>
</div>
@endvolt
