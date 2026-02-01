<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Common Portal') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ $favicon ?? '/images/platform-defaults/favicon.png' }}">

    <!-- Open Graph / Social Sharing -->
    <meta property="og:title" content="{{ $title ?? config('app.name', 'Common Portal') }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Wholesale, High-Volume Fiat<>Crypto' }}">
    <meta property="og:image" content="{{ $metaImage ?? '/images/platform-defaults/meta-card-preview.png' }}">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Iconify -->
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>

    <!-- Theme CSS Variables -->
    <style>
        :root {
            --brand-primary-color: {{ $themeColors['--brand-primary-color'] ?? '#00ff88' }};
            --brand-secondary-color: {{ $themeColors['--brand-secondary-color'] ?? '#0088ff' }};
            --content-background-color: #0f0f1a;
            --content-text-color: #e0e0e0;
            --card-background-color: #1a1a2e;
            --header-background-color: #0a0a14;
            --footer-background-color: #0a0a14;
            --button-text-color: {{ $themeColors['--button-text-color'] ?? '#1a1a2e' }};
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="font-sans antialiased min-h-screen flex flex-col" style="background-color: var(--content-background-color); color: var(--content-text-color);">
    
    <!-- Header -->
    <header class="sticky top-0 z-50 px-6 py-4" style="background-color: var(--header-background-color);">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Left: Logo + Name -->
            <a href="/" class="flex items-center space-x-3">
                <img src="{{ $platformLogo ?? '/images/platform-defaults/platform-logo.png' }}" 
                     alt="{{ $platformName ?? 'Common Portal' }}" 
                     class="h-10 w-auto">
                <span class="text-3xl" style="color: #e3be3b; font-weight: 900;">
                    {{ $platformName ?? 'Common Portal' }}
                </span>
            </a>

            <!-- Center: Navigation -->
            <nav class="hidden md:flex items-center space-x-6">
                <a href="/support" class="flex items-center gap-2 text-base font-medium opacity-70 hover:opacity-100 transition-opacity">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                    </svg>
                    {{ __translator('Support') }}
                </a>
            </nav>

            <!-- Right: Login / Register -->
            <div class="flex items-center space-x-3">
                <a href="/login-register" 
                   class="px-4 py-2 text-sm font-medium rounded-md border transition-colors hover:opacity-80"
                   style="border-color: var(--brand-primary-color); color: var(--brand-primary-color);">
                    {{ __translator('Login') }}
                </a>
                <a href="/login-register" 
                   class="px-4 py-2 text-sm font-medium rounded-md transition-colors hover:opacity-90"
                   style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    {{ __translator('Register') }}
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <!-- Footer -->
    <footer class="px-6 py-4" style="background-color: var(--footer-background-color);">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Left: Language Selector -->
            <div>
                @include('components.language-selector')
            </div>

            <!-- Center: Powered By -->
            <div class="text-sm opacity-60">
                {{ __translator('Powered by') }} <span style="color: var(--brand-primary-color);">CIDRUS SP ZOO</span>
            </div>

            <!-- Right: Terms of Service -->
            <div x-data="{ showTos: false }">
                <button @click="showTos = true" 
                        class="text-sm opacity-60 hover:opacity-100 underline transition-opacity">
                    {{ __translator('Terms of Service') }}
                </button>

                <!-- Terms of Service Modal -->
                <div x-show="showTos" 
                     x-cloak
                     @keydown.escape.window="showTos = false"
                     class="fixed inset-0 z-50 overflow-y-auto"
                     style="background-color: rgba(0,0,0,0.85);">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="relative w-full max-w-3xl rounded-lg p-8 text-left"
                             style="background-color: var(--card-background-color); max-height: 85vh; overflow-y: auto;"
                             @click.away="showTos = false">
                            
                            <!-- Close Button -->
                            <button @click="showTos = false" 
                                    class="absolute top-4 right-4 text-2xl opacity-70 hover:opacity-100 leading-none">&times;</button>
                            
                            <!-- Modal Header -->
                            <h2 class="text-2xl font-bold mb-4" style="color: var(--brand-primary-color);">
                                {{ __translator('Terms of Service') }}
                            </h2>
                            <p class="text-sm opacity-60 mb-6">{{ __translator('CIDRUS SP. Z O.O. — Wholesale Crypto Exchange Services Agreement') }}</p>
                            
                            <!-- Section 1 -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-3" style="color: #4ade80;">1. {{ __translator('Service Provider') }}</h3>
                                <table class="w-full text-sm" style="border-collapse: separate; border-spacing: 0 0.25rem;">
                                    <tr><td class="opacity-60 pr-4" style="width: 120px;">{{ __translator('Company') }}</td><td>CIDRUS SP. Z O.O.</td></tr>
                                    <tr><td class="opacity-60 pr-4">{{ __translator('Jurisdiction') }}</td><td>{{ __translator('Poland (EU)') }}</td></tr>
                                    <tr><td class="opacity-60 pr-4">{{ __translator('License') }}</td><td>{{ __translator('MSB / VASP') }}</td></tr>
                                    <tr><td class="opacity-60 pr-4">{{ __translator('Brand') }}</td><td>XRAMP.io</td></tr>
                                </table>
                            </div>
                            
                            <!-- Section 2 -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-3" style="color: #4ade80;">2. {{ __translator('Services Provided') }}</h3>
                                <p class="opacity-80 text-sm mb-2">{{ __translator('Wholesale fiat-to-crypto and crypto-to-fiat conversion for institutional clients.') }}</p>
                                <ul class="text-sm opacity-70 space-y-1">
                                    <li>▸ {{ __translator('EUR, USD, GBP to USDC, EURC, BTC, ETH') }}</li>
                                    <li>▸ {{ __translator('SEPA/SWIFT bank transfers') }}</li>
                                    <li>▸ {{ __translator('1-5 day settlement') }}</li>
                                    <li>▸ {{ __translator('€50,000 minimum') }}</li>
                                </ul>
                            </div>
                            
                            <!-- Section 3 -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-3" style="color: #4ade80;">3. {{ __translator('Client Eligibility') }}</h3>
                                <ul class="text-sm opacity-70 space-y-1">
                                    <li>▸ {{ __translator('Registered businesses and corporations') }}</li>
                                    <li>▸ {{ __translator('Licensed financial institutions') }}</li>
                                    <li>▸ {{ __translator('Accredited institutional investors') }}</li>
                                </ul>
                                <p class="text-xs opacity-50 mt-2">{{ __translator('Retail clients not eligible.') }}</p>
                            </div>
                            
                            <!-- Section 4 -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-3" style="color: #4ade80;">4. {{ __translator('Compliance') }}</h3>
                                <ul class="text-sm opacity-70 space-y-1">
                                    <li>▸ {{ __translator('KYC/KYB per Polish AML regulations') }}</li>
                                    <li>▸ {{ __translator('EU 5AMLD / MiCA compliant') }}</li>
                                    <li>▸ {{ __translator('Segregated client funds') }}</li>
                                    <li>▸ {{ __translator('Multi-signature custody') }}</li>
                                </ul>
                            </div>
                            
                            <!-- Section 5 -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-3" style="color: #4ade80;">5. {{ __translator('Fees') }}</h3>
                                <p class="text-sm opacity-70">{{ __translator('Per-client pricing based on volume, currency pairs, and settlement speed.') }}</p>
                            </div>
                            
                            <!-- Section 6 -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-3" style="color: #4ade80;">6. {{ __translator('Risk Disclosure') }}</h3>
                                <p class="text-sm opacity-70">{{ __translator('Crypto transactions involve risks including volatility, regulatory changes, and counterparty risks. Clients confirm financial capacity for wholesale transactions.') }}</p>
                            </div>
                            
                            <!-- Close Button -->
                            <div class="mt-6 text-center">
                                <button @click="showTos = false"
                                        class="px-6 py-2 text-sm font-bold rounded-md transition-colors hover:opacity-90"
                                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                    {{ __translator('Close') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    @stack('modals')
    @stack('scripts')
    
    <!-- Global Submit Button Handler: Disable + Spinner on Click -->
    <script>
        const PROCESSING_TEXT = '{{ __translator("Processing...") }}';
        
        document.addEventListener('DOMContentLoaded', function() {
            // Handle all forms with data-submit-button class
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const btn = form.querySelector('button[type="submit"], button:not([type])');
                    if (btn && !btn.disabled) {
                        // Store original content
                        btn.dataset.originalText = btn.innerHTML;
                        // Disable and show spinner
                        btn.disabled = true;
                        btn.innerHTML = '<svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> ' + PROCESSING_TEXT;
                        btn.style.opacity = '0.7';
                        btn.style.cursor = 'not-allowed';
                    }
                });
            });
        });
        
        // Revert button on page show (back/forward navigation)
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                document.querySelectorAll('button[data-original-text]').forEach(function(btn) {
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.originalText;
                    btn.style.opacity = '';
                    btn.style.cursor = '';
                });
            }
        });
    </script>
    
    @livewireScripts
</body>
</html>
