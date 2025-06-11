<?php

use function Laravel\Folio\name;

name('home');

?>

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Twickenham Property Ground - Your Home, Simplified</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 text-white">
<!-- Navigation -->
<nav class="bg-gray-800 border-b border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="text-xl font-bold text-green-400">
                Twickenham Property Ground
            </div>
            <div class="hidden md:flex space-x-8">
                <a href="{{route('login')}}" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                    Tenant Login
                </a>
                <a href="#" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                    Support
                </a>
            </div>
            <button class="md:hidden text-gray-300 hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-gray-900 to-gray-800 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-green-900 bg-opacity-50 text-green-300 px-4 py-2 rounded-full text-sm inline-block mb-8">
            🏠 Welcome Home, Simplified
        </div>
        <h1 class="text-4xl md:text-6xl font-bold mb-6">
            Your Home at Your Fingertips
        </h1>
        <p class="text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
            Manage your rental life effortlessly with our tenant portal. Pay rent, submit maintenance requests, communicate with your landlord, and access important documents - all in one secure place.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{route('Portal')}" class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                Access Tenant Portal
            </a>
            <a href="#features" class="bg-gray-700 hover:bg-gray-600 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                Learn More
            </a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="bg-gray-800 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-3xl font-bold text-green-400 mb-2">50K+</div>
                <div class="text-gray-400">Happy Tenants</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-green-400 mb-2">4.9/5</div>
                <div class="text-gray-400">User Satisfaction</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-green-400 mb-2">24/7</div>
                <div class="text-gray-400">Online Access</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-green-400 mb-2">2 Min</div>
                <div class="text-gray-400">Average Response</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Everything You Need</h2>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                Your complete rental management toolkit designed to make your life easier and your home experience better.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-green-500 transition-colors">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-2xl mb-4">
                    💳
                </div>
                <h3 class="text-xl font-semibold mb-3">Easy Rent Payments</h3>
                <p class="text-gray-400">
                    Pay your rent online with multiple payment options. Set up autopay, view payment history, and never miss a due date with automated reminders.
                </p>
            </div>

            <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-green-500 transition-colors">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-2xl mb-4">
                    🔧
                </div>
                <h3 class="text-xl font-semibold mb-3">Maintenance Requests</h3>
                <p class="text-gray-400">
                    Submit maintenance requests instantly with photos and descriptions. Track progress in real-time and get updates on repair schedules.
                </p>
            </div>

            <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-green-500 transition-colors">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-2xl mb-4">
                    💬
                </div>
                <h3 class="text-xl font-semibold mb-3">Direct Communication</h3>
                <p class="text-gray-400">
                    Message your property manager directly through the secure portal. Get quick responses and keep all communications organized in one place.
                </p>
            </div>

            <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-green-500 transition-colors">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-2xl mb-4">
                    📄
                </div>
                <h3 class="text-xl font-semibold mb-3">Document Access</h3>
                <p class="text-gray-400">
                    Access your lease agreement, rent receipts, notices, and important documents anytime, anywhere. Everything stored securely in the cloud.
                </p>
            </div>

            <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-green-500 transition-colors">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-2xl mb-4">
                    🏘️
                </div>
                <h3 class="text-xl font-semibold mb-3">Community Features</h3>
                <p class="text-gray-400">
                    Connect with neighbors, view community announcements, and stay informed about building news and events through the resident network.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-20 bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">What Tenants Say</h2>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                Real feedback from real tenants who love using our platform every day.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                <p class="text-gray-300 mb-6 italic">
                    "Finally, a tenant portal that actually works! Paying rent is so easy now, and I love getting instant updates on my maintenance requests. It's made my rental experience so much better."
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                        SM
                    </div>
                    <div>
                        <div class="font-semibold">Sarah Mitchell</div>
                        <div class="text-gray-400 text-sm">Riverside Apartments</div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                <p class="text-gray-300 mb-6 italic">
                    "The mobile app is a game-changer. I can submit maintenance requests with photos while I'm at work, and my property manager responds within hours. It's incredibly convenient."
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                        DJ
                    </div>
                    <div>
                        <div class="font-semibold">David Johnson</div>
                        <div class="text-gray-400 text-sm">Oak Gardens Complex</div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                <p class="text-gray-300 mb-6 italic">
                    "I love how organized everything is. All my documents in one place, payment history at my fingertips, and direct communication with management. It's professional and user-friendly."
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                        ER
                    </div>
                    <div>
                        <div class="font-semibold">Emily Rodriguez</div>
                        <div class="text-gray-400 text-sm">Metro Heights</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<!-- Footer -->
<footer class="bg-gray-900 border-t border-gray-200 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center space-y-6">
            <!-- Company Name -->
            <div class="text-2xl font-semibold text-white">
                Twickenham Property Ground
            </div>

            <!-- Professional tagline -->
            <p class="text-gray-300 text-lg max-w-2xl mx-auto">
                Your trusted partner in Twickenham property services
            </p>

            <!-- Contact Information -->
            <div class="text-gray-400 space-y-2">
                <p>Professional Property Services in Twickenham</p>
                <p>Established with Excellence • Committed to Quality</p>
            </div>

            <!-- Copyright -->
            <div class="pt-6 border-t border-gray-700">
                <p class="text-gray-500 text-sm">
                    © 2024 Twickenham Property Ground. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<script>
    // Simple smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
</script>
</body>
</html>
