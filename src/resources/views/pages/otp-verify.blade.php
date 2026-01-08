@extends('layouts.guest')

@section('content')
{{-- OTP Verification Page --}}

<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
<div class="w-full max-w-md">
    <div class="rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-2 text-center">{{ __translator('Enter Verification Code') }}</h1>
        
        <p class="text-center opacity-70 mb-6">
            {{ __translator('We sent a 6-digit code to') }}<br>
            <strong>{{ $email }}</strong>
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

        <form method="POST" action="{{ route('otp.verify') }}">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">{{ __translator('Verification Code') }}</label>
                <input type="text" 
                       name="code" 
                       class="w-full px-4 py-3 rounded-md border-0 focus:ring-2 text-center text-2xl tracking-widest"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="000000"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       autofocus
                       required>
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors mb-4"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Verify Code') }}
            </button>
        </form>

        <div class="text-center">
            <p class="text-sm opacity-70 mb-2">{{ __translator("Didn't receive the code?") }}</p>
            <form method="POST" action="{{ route('otp.resend') }}" class="inline">
                @csrf
                <button type="submit" 
                        class="text-sm font-medium hover:underline"
                        style="color: var(--hyperlink-text-color);">
                    {{ __translator('Resend Code') }}
                </button>
            </form>
        </div>

        <div class="mt-6 pt-4 border-t text-center" style="border-color: var(--sidebar-hover-background-color);">
            <a href="{{ route('login-register') }}" 
               class="text-sm opacity-70 hover:opacity-100">
                {{ __translator('Use a different email') }}
            </a>
        </div>
    </div>
</div>
</div>
@endsection
