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
            --sidebar-background-color: {{ $themeColors['--sidebar-background-color'] ?? '#1a1a2e' }};
            --sidebar-text-color: {{ $themeColors['--sidebar-text-color'] ?? '#e0e0e0' }};
            --sidebar-hover-background-color: {{ $themeColors['--sidebar-hover-background-color'] ?? '#2a2a4e' }};
            --sidebar-width: 260px;
            --brand-primary-color: {{ $themeColors['--brand-primary-color'] ?? '#00ff88' }};
            --brand-secondary-color: {{ $themeColors['--brand-secondary-color'] ?? '#0088ff' }};
            --status-success-color: {{ $themeColors['--status-success-color'] ?? '#22c55e' }};
            --status-warning-color: {{ $themeColors['--status-warning-color'] ?? '#eab308' }};
            --status-error-color: {{ $themeColors['--status-error-color'] ?? '#ef4444' }};
            --hyperlink-text-color: {{ $themeColors['--hyperlink-text-color'] ?? '#3b82f6' }};
            --button-background-color: {{ $themeColors['--button-background-color'] ?? '#00ff88' }};
            --button-text-color: {{ $themeColors['--button-text-color'] ?? '#1a1a2e' }};
            --content-background-color: #0f0f1a;
            --content-text-color: #e0e0e0;
            --card-background-color: #1a1a2e;
            --admin-banner-background-color: #dc2626;
            --admin-banner-text-color: #ffffff;
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased" style="background-color: var(--content-background-color); color: var(--content-text-color);">
    
    <!-- Admin Impersonation Banner -->
    @if(session('admin_impersonating_from'))
    @php
        $impersonatedAccount = \App\Models\TenantAccount::find(session('active_account_id'));
    @endphp
    <div id="admin-banner" class="fixed top-0 left-0 right-0 z-50 px-4 py-2 text-center text-sm font-medium" 
         style="background-color: var(--admin-banner-background-color); color: var(--admin-banner-text-color);">
        {{ __translator('ADMIN VIEW') }}: {{ __translator('Managing account') }} "<strong>{{ $impersonatedAccount?->account_display_name ?? 'Unknown' }}</strong>" 
        ({{ $impersonatedAccount?->primary_contact_email_address ?? '' }})
        <a href="{{ route('admin.exit-impersonation') }}" class="ml-4 underline hover:no-underline">{{ __translator('Exit Admin View') }}</a>
    </div>
    @endif

    <div class="flex {{ session('admin_impersonating_from') ? 'pt-10' : '' }}">
        
        <!-- Sidebar: always visible on md+, off-screen toggle on mobile -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex flex-col h-screen transition-transform duration-300
                      -translate-x-full md:translate-x-0 md:relative md:shrink-0"
               style="width: var(--sidebar-width); background-color: var(--sidebar-background-color); color: var(--sidebar-text-color);">
            
            <!-- Platform Logo -->
            <div class="shrink-0 p-4 border-b" style="border-color: var(--sidebar-hover-background-color);">
                <a href="/" class="flex items-center space-x-3">
                    <img src="{{ $platformLogo ?? '/images/platform-defaults/platform-logo.png' }}" 
                         alt="{{ $platformName ?? 'Common Portal' }}" 
                         class="h-8 w-auto">
                    <span class="text-lg" style="color: #e3be3b; font-weight: 900;">{{ $platformName ?? 'Common Portal' }}</span>
                </a>
            </div>

            <!-- Sidebar Content -->
            <nav class="flex-1 overflow-y-auto p-4">
                @include('components.sidebar-menu')
            </nav>

            <!-- Language Selector (Bottom) -->
            <div class="language-selector-container shrink-0 p-4 border-t" style="border-color: var(--sidebar-hover-background-color); background-color: var(--sidebar-background-color);">
                @include('components.language-selector')
            </div>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div id="sidebar-overlay" class="md:hidden fixed inset-0 bg-black bg-opacity-50 z-30 hidden" 
             onclick="toggleSidebar()"></div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            
            <!-- Mobile Header -->
            <header class="mobile-header sticky top-0 z-20 flex items-center justify-between p-4"
                    style="background-color: var(--sidebar-background-color);">
                <button onclick="toggleSidebar()" class="p-2 rounded-md hover:opacity-80" 
                        style="color: var(--sidebar-text-color);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="font-semibold" style="color: var(--sidebar-text-color);">
                    {{ $platformName ?? 'Common Portal' }}
                </span>
                <div class="w-10"></div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 md:p-8">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="p-4 text-center text-sm opacity-60"
                    style="background-color: var(--sidebar-background-color); color: var(--sidebar-text-color);">
                <p>{{ __translator('Powered by') }} <a href="https://nsdb.com" target="_NSDB" class="hover:opacity-80" style="color: var(--brand-primary-color);">NSDB.COM</a> · {{ __translator('CC0 1.0 Universal - No Rights Reserved') }}</p>
            </footer>
        </div>
    </div>

    <!-- Sidebar Responsive CSS (explicit media query) -->
    <style>
        /* Mobile: sidebar hidden off-screen */
        #sidebar {
            transform: translateX(-100%);
            position: fixed;
        }
        #sidebar.translate-x-0 {
            transform: translateX(0);
        }
        /* Desktop (768px+): sidebar always visible, sticky, full height */
        @media (min-width: 768px) {
            #sidebar {
                transform: translateX(0) !important;
                position: sticky !important;
                top: 0 !important;
                height: 100vh !important;
                flex-shrink: 0 !important;
            }
            .mobile-header {
                display: none !important;
            }
        }
        /* Sidebar flexbox layout for language selector at bottom */
        #sidebar {
            display: flex !important;
            flex-direction: column !important;
        }
        #sidebar .language-selector-container {
            margin-top: auto !important;
            flex-shrink: 0 !important;
        }
    </style>

    <!-- Sidebar Toggle Script (mobile only) -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('translate-x-0');
            overlay.classList.toggle('hidden');
        }
    </script>

    @stack('modals')
    
    <!-- Global Submit Button Handler: Disable + Spinner on Click -->
    <script>
        const PROCESSING_TEXT = '{{ __translator("Processing...") }}';
        
        document.addEventListener('DOMContentLoaded', function() {
            // Handle all forms with submit buttons
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
    
    @stack('scripts')
    @livewireScripts
</body>
</html>
