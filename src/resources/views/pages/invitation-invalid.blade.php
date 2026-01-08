@extends('layouts.platform')

@section('content')
{{-- Invalid Invitation Page --}}

<div class="max-w-md mx-auto mt-10">
    <div class="rounded-lg p-8 text-center" style="background-color: var(--card-background-color);">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" 
             style="background-color: var(--status-error-color);">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold mb-2">{{ __translator('Invalid Invitation') }}</h1>
        <p class="opacity-70 mb-6">{{ $reason }}</p>

        <a href="{{ route('home') }}" 
           class="inline-block px-6 py-3 rounded-md font-medium"
           style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
            {{ __translator('Go to Home') }}
        </a>
    </div>
</div>
@endsection
