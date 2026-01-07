@extends('layouts.platform')

@section('content')
{{-- Invitation Accept Page --}}

<div class="max-w-md mx-auto mt-10">
    <div class="rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-2">You're Invited!</h1>
        <p class="opacity-70 mb-6">
            <strong>{{ $inviter->full_name }}</strong> has invited you to join 
            <strong>{{ $account->account_display_name }}</strong>.
        </p>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <div class="mb-6 p-4 rounded-lg" style="background-color: var(--content-background-color);">
            <p class="text-sm font-medium mb-2">Invitation Details</p>
            <p class="text-sm opacity-70">Email: {{ $invitation->invited_email_address }}</p>
            <p class="text-sm opacity-70">Sent: {{ $invitation->created_at_timestamp->diffForHumans() }}</p>
        </div>

        @auth
            @if(strtolower(auth()->user()->login_email_address) === strtolower($invitation->invited_email_address))
            <form method="POST" action="{{ route('invitation.accept.submit', $invitation->record_unique_identifier) }}">
                @csrf
                <button type="submit" 
                        class="w-full px-4 py-3 rounded-md font-medium"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    Accept Invitation
                </button>
            </form>
            @else
            <div class="p-4 rounded-lg mb-4" style="background-color: var(--status-warning-color); color: #1a1a2e;">
                <p class="text-sm font-medium">Wrong Account</p>
                <p class="text-sm">You're logged in as {{ auth()->user()->login_email_address }}.</p>
                <p class="text-sm">This invitation is for {{ $invitation->invited_email_address }}.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="w-full px-4 py-3 rounded-md font-medium"
                        style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);">
                    Log Out & Switch Account
                </button>
            </form>
            @endif
        @else
            <p class="text-sm opacity-70 mb-4">Please log in or register to accept this invitation.</p>
            <a href="{{ route('login-register') }}" 
               class="block w-full px-4 py-3 rounded-md font-medium text-center"
               style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Log In / Register
            </a>
        @endauth

        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-sm opacity-70 hover:opacity-100">
                ← Back to Home
            </a>
        </div>
    </div>
</div>
@endsection
