@extends('layouts.platform')

@section('content')
{{-- Login/Register Page (Placeholder for Phase 3: Authentication) --}}

<div class="max-w-md mx-auto mt-10">
    <div class="rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-6 text-center">Login or Register</h1>
        
        <p class="text-center opacity-70 mb-6">
            Enter your email to receive a one-time password.
        </p>

        {{-- Placeholder form - will be implemented in Phase 3 --}}
        <form method="POST" action="#">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Email Address</label>
                <input type="email" 
                       name="email" 
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="you@example.com"
                       required>
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Continue with Email
            </button>
        </form>

        <p class="text-center text-sm opacity-60 mt-6">
            We'll send you a one-time code to verify your email.
        </p>
    </div>
</div>
@endsection
