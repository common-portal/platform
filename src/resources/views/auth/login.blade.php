@extends('layouts.guest')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-md rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-6 text-center">{{ __translator('Login') }}</h1>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        @session('status')
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
            {{ $value }}
        </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" id="login-form">
            @csrf
            <input type="hidden" name="recaptcha_token" id="login-recaptcha-token">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Email Address') }}</label>
                <input type="email" 
                       name="login_email_address" 
                       value="{{ old('login_email_address') }}"
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="you@example.com"
                       required 
                       autofocus 
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
                       autocomplete="current-password">
            </div>

            <div class="mb-4 flex items-center">
                <input type="checkbox" name="remember" id="remember" class="mr-2">
                <label for="remember" class="text-sm opacity-70">{{ __translator('Remember me') }}</label>
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Log in') }}
            </button>

            @if (Route::has('password.request'))
            <div class="mt-4 text-center">
                <a href="{{ route('password.request') }}" 
                   class="text-sm hover:underline"
                   style="color: var(--hyperlink-text-color);">
                    {{ __translator('Forgot your password?') }}
                </a>
            </div>
            @endif
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

@push('head')
@if(config('recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}"></script>
<style>
    .grecaptcha-badge {
        bottom: 70px !important;
    }
</style>
@endif
@endpush

@push('scripts')
<script>
    const RECAPTCHA_SITE_KEY = '{{ config('recaptcha.site_key') }}';
    
    if (RECAPTCHA_SITE_KEY && typeof grecaptcha !== 'undefined') {
        grecaptcha.ready(function() {
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: 'login'}).then(function(token) {
                        document.getElementById('login-recaptcha-token').value = token;
                        loginForm.submit();
                    });
                });
            }
        });
    }
</script>
@endpush
