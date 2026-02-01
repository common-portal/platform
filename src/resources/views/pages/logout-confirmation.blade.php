@extends('layouts.platform')

@section('content')
<div class="max-w-md mx-auto">
    <div class="rounded-lg p-8 text-center" style="background-color: var(--card-background-color);">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        
        <h1 class="text-2xl font-bold mb-4">{{ __translator('Confirm Logout') }}</h1>
        <p class="mb-6 opacity-70">{{ __translator('Are you sure you want to log out?') }}</p>
        
        <div class="flex gap-3 justify-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="px-6 py-2 rounded-md font-medium hover:opacity-80"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    {{ __translator('Yes, Logout') }}
                </button>
            </form>
            
            <a href="{{ url()->previous() }}" 
               class="px-6 py-2 rounded-md font-medium hover:opacity-80"
               style="background-color: var(--card-background-color); border: 1px solid rgba(255,255,255,0.1);">
                {{ __translator('Cancel') }}
            </a>
        </div>
    </div>
</div>
@endsection
