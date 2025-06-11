<!-- Absolute Flash Messages - Add this to your main layout -->

@if (session('message') || session('success') || session('error') || session('warning'))
    <div class="fixed top-4 right-4 z-50 max-w-md">

        @if (session('message') || session('success'))
            <div class="mb-3 bg-green-500 bg-opacity-20 border border-green-500 text-green-400 px-4 py-3 rounded-lg shadow-xl backdrop-blur-sm animate-slide-in">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm font-medium">{{ session('message') ?? session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-green-400 hover:text-green-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
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
        @endif

        @if (session('warning'))
            <div class="mb-3 bg-yellow-500 bg-opacity-20 border border-yellow-500 text-yellow-400 px-4 py-3 rounded-lg shadow-xl backdrop-blur-sm animate-slide-in">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="text-sm font-medium">{{ session('warning') }}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-yellow-400 hover:text-yellow-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

    </div>

    <!-- Auto-hide after 5 seconds -->
    <script>
        setTimeout(function() {
            const flashMessages = document.querySelector('.fixed.top-4.right-4');
            if (flashMessages) {
                flashMessages.style.opacity = '0';
                flashMessages.style.transform = 'translateX(100%)';
                setTimeout(() => flashMessages.remove(), 300);
            }
        }, 5000);
    </script>

    <style>
        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
    </style>
@endif
