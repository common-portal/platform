@extends('layouts.platform')

@section('content')
{{-- Email Verification Page --}}

<div class="max-w-md mx-auto mt-10">
    <div class="rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-2">Verify New Email</h1>
        <p class="opacity-70 mb-6">
            We sent a 6-digit code to <strong>{{ $pendingEmail }}</strong>
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

        <form method="POST" action="{{ route('member.settings.email.verify.submit') }}">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Verification Code</label>
                <input type="text" 
                       name="code" 
                       class="w-full px-4 py-3 rounded-md border-0 text-center text-2xl tracking-widest"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       autofocus
                       required>
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Verify & Update Email
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('member.settings') }}" class="text-sm opacity-70 hover:opacity-100">
                ← Cancel
            </a>
        </div>
    </div>
</div>
@endsection
