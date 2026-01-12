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
    <meta property="og:description" content="{{ $metaDescription ?? 'A white-label, multi-tenant portal platform.' }}">
    <meta property="og:image" content="{{ $metaImage ?? '/images/platform-defaults/meta-card-preview.png' }}">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

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
    
    <!-- reCAPTCHA v3 -->
    @if(config('recaptcha.site_key'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}"></script>
    @endif
</head>
<body class="font-sans antialiased min-h-screen flex flex-col" style="background-color: var(--content-background-color); color: var(--content-text-color);">
    
    <!-- Header -->
    <header class="sticky top-0 z-50 px-6 py-4" style="background-color: var(--header-background-color);">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Left: Logo + Name -->
            <a href="/" class="flex items-center space-x-3">
                <img src="{{ $platformLogo ?? '/images/platform-defaults/platform-logo.png' }}" 
                     alt="{{ $platformName ?? 'Common Portal' }}" 
                     class="h-8 w-auto">
                <span class="text-lg" style="color: #e3be3b; font-weight: 900;">
                    {{ $platformName ?? 'Common Portal' }}
                </span>
            </a>

            <!-- Center: Navigation -->
            <nav class="hidden md:flex items-center space-x-6">
                <a href="/support" class="text-sm opacity-70 hover:opacity-100 transition-opacity">
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
                {{ __translator('Powered by') }} <a href="https://nsdb.com" target="_NSDB" class="hover:opacity-80" style="color: var(--brand-primary-color);">NSDB.COM</a>
            </div>

            <!-- Right: Copyright -->
            <div class="text-sm opacity-60">
                {{ __translator('CC0 1.0 Universal - No Rights Reserved') }}
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
