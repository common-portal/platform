@extends('layouts.guest')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-md rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-2 text-center">{{ __translator('Verify Email') }}</h1>
        
        <p class="text-center opacity-70 mb-6">
            {{ __translator('Please verify your email address by clicking on the link we just emailed to you.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
            {{ __translator('A new verification link has been sent to your email address.') }}
        </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors mb-4"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Resend Verification Email') }}
            </button>
        </form>

        <div class="flex items-center justify-between text-sm">
            <a href="{{ route('profile.show') }}" 
               class="hover:underline"
               style="color: var(--hyperlink-text-color);">
                {{ __translator('Edit Profile') }}
            </a>

            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" 
                        class="hover:underline"
                        style="color: var(--hyperlink-text-color);">
                    {{ __translator('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
