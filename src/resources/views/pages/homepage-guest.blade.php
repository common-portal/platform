@extends('layouts.guest')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[70vh] px-6 py-16">
    
    {{-- Hero: Big Logo --}}
    <div class="mb-8">
        <img src="{{ $platformLogo ?? '/images/platform-defaults/platform-logo.png' }}" 
             alt="{{ $platformName ?? 'Common Portal' }}" 
             class="h-32 w-auto mx-auto">
    </div>

    {{-- Platform Name --}}
    <h1 class="text-4xl md:text-5xl font-bold mb-4 text-center">
        {{ $platformName ?? 'Common Portal' }}
    </h1>

    {{-- Tagline --}}
    <p class="text-xl opacity-70 mb-10 text-center max-w-2xl">
        {{ __translator('A white-label, multi-tenant portal platform.') }}
    </p>

    {{-- CTA Buttons --}}
    <div class="flex items-center space-x-4">
        <a href="/login-register" 
           class="px-8 py-3 text-lg font-medium rounded-md transition-colors hover:opacity-90"
           style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
            {{ __translator('Get Started') }}
        </a>
        <a href="/support" 
           class="px-8 py-3 text-lg font-medium rounded-md border transition-colors hover:opacity-80"
           style="border-color: var(--brand-primary-color); color: var(--brand-primary-color);">
            {{ __translator('Learn More') }}
        </a>
    </div>

</div>
@endsection
