@extends('layouts.guest')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-md rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-2 text-center">{{ __translator('Confirm Password') }}</h1>
        
        <p class="text-center opacity-70 mb-6">
            {{ __translator('This is a secure area. Please confirm your password before continuing.') }}
        </p>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Password') }}</label>
                <input type="password" 
                       name="password" 
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="••••••••"
                       required 
                       autofocus 
                       autocomplete="current-password">
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Confirm') }}
            </button>
        </form>
    </div>
</div>
@endsection
