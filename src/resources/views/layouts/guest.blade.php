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
                <span class="text-lg font-semibold" style="color: var(--content-text-color);">
                    {{ $platformName ?? 'Common Portal' }}
                </span>
            </a>

            <!-- Center: Navigation -->
            <nav class="hidden md:flex items-center space-x-6">
                <a href="/support" class="text-sm opacity-70 hover:opacity-100 transition-opacity">
                    Support
                </a>
            </nav>

            <!-- Right: Login / Register -->
            <div class="flex items-center space-x-3">
                <a href="/login-register" 
                   class="px-4 py-2 text-sm font-medium rounded-md border transition-colors hover:opacity-80"
                   style="border-color: var(--brand-primary-color); color: var(--brand-primary-color);">
                    Login
                </a>
                <a href="/login-register" 
                   class="px-4 py-2 text-sm font-medium rounded-md transition-colors hover:opacity-90"
                   style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    Register
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

            <!-- Right: Copyright -->
            <div class="text-sm opacity-60">
                CC0 1.0 Universal - No Rights Reserved
            </div>
        </div>
    </footer>

    @stack('modals')
    @livewireScripts
</body>
</html>
