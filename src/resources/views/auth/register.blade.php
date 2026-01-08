@extends('layouts.guest')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-md rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-6 text-center">{{ __translator('Register') }}</h1>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Name') }}</label>
                <input type="text" 
                       name="name" 
                       value="{{ old('name') }}"
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="{{ __translator('Your name') }}"
                       required 
                       autofocus 
                       autocomplete="name">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Email Address') }}</label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email') }}"
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="you@example.com"
                       required 
                       autocomplete="username">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Password') }}</label>
                <input type="password" 
                       name="password" 
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="••••••••"
                       required 
                       autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Confirm Password') }}</label>
                <input type="password" 
                       name="password_confirmation" 
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="••••••••"
                       required 
                       autocomplete="new-password">
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
            <div class="mb-4 flex items-center">
                <input type="checkbox" name="terms" id="terms" class="mr-2" required>
                <label for="terms" class="text-sm opacity-70">
                    {{ __translator('I agree to the') }} <a href="{{ route('terms.show') }}" target="_blank" class="underline" style="color: var(--hyperlink-text-color);">{{ __translator('Terms of Service') }}</a> {{ __translator('and') }} <a href="{{ route('policy.show') }}" target="_blank" class="underline" style="color: var(--hyperlink-text-color);">{{ __translator('Privacy Policy') }}</a>
                </label>
            </div>
            @endif

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Register') }}
            </button>
        </form>

        <div class="mt-6 pt-4 border-t text-center" style="border-color: var(--sidebar-hover-background-color);">
            <a href="{{ route('login-register') }}" 
               class="text-sm opacity-70 hover:opacity-100">
                {{ __translator('Back to Login/Register') }}
            </a>
        </div>
    </div>
</div>
@endsection
