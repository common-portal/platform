@extends('layouts.guest')

@section('content')
{{-- Login/Register Page - Matches homepage layout --}}

<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
    {{-- Login/Register Card --}}
    <div class="w-full max-w-md rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-2 text-center">{{ __translator('Login or Register') }}</h1>
        
        <p class="text-center opacity-70 mb-6">
            {{ __translator('Enter your email to receive a one-time password.') }}
        </p>

        @if(session('status'))
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
            {{ session('status') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        {{-- OTP Login Form (Primary) --}}
        <form method="POST" action="{{ route('otp.send') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Email Address') }}</label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email') }}"
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="you@example.com"
                       autofocus
                       required>
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Continue with Email') }}
            </button>
        </form>

        <p class="text-center text-sm opacity-60 mt-4">
            {{ __translator("We'll send you a one-time code to verify your email.") }}
        </p>

        {{-- Password Login Toggle (Optional) --}}
        <div class="mt-6 pt-4 border-t" style="border-color: var(--sidebar-hover-background-color);">
            <button type="button" 
                    onclick="togglePasswordLogin()"
                    class="w-full text-center text-sm opacity-70 hover:opacity-100"
                    style="color: var(--hyperlink-text-color);">
                {{ __translator('Login with password instead') }}
            </button>

            <div id="password-login-form" class="hidden mt-4">
                <form method="POST" action="{{ route('login.password') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">{{ __translator('Email Address') }}</label>
                        <input type="email" 
                               name="email" 
                               class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                               style="background-color: var(--content-background-color); color: var(--content-text-color);"
                               placeholder="you@example.com"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">{{ __translator('Password') }}</label>
                        <input type="password" 
                               name="password" 
                               class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                               style="background-color: var(--content-background-color); color: var(--content-text-color);"
                               placeholder="••••••••"
                               required>
                    </div>

                    <div class="mb-4 flex items-center">
                        <input type="checkbox" name="remember" id="remember" class="mr-2">
                        <label for="remember" class="text-sm opacity-70">{{ __translator('Remember me') }}</label>
                    </div>

                    <button type="submit" 
                            class="w-full px-4 py-3 rounded-md font-medium transition-colors"
                            style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);">
                        {{ __translator('Login with Password') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function togglePasswordLogin() {
        const form = document.getElementById('password-login-form');
        form.classList.toggle('hidden');
    }
</script>
@endpush
@endsection
