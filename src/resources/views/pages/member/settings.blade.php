@extends('layouts.platform')

@section('content')
{{-- Member Settings Page --}}

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">My Profile</h1>

    {{-- Profile Tab --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Profile</h2>
        
        <form method="POST" action="#">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2">First Name</label>
                    <input type="text" 
                           name="member_first_name" 
                           value="{{ auth()->user()->member_first_name ?? '' }}"
                           class="w-full px-4 py-2 rounded-md border-0"
                           style="background-color: var(--content-background-color); color: var(--content-text-color);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Last Name</label>
                    <input type="text" 
                           name="member_last_name" 
                           value="{{ auth()->user()->member_last_name ?? '' }}"
                           class="w-full px-4 py-2 rounded-md border-0"
                           style="background-color: var(--content-background-color); color: var(--content-text-color);">
                </div>
            </div>

            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Update Profile
            </button>
        </form>
    </div>

    {{-- Login Email Tab --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Login Email</h2>
        
        <p class="mb-4 opacity-70">Current email: <strong>{{ auth()->user()->login_email_address ?? 'N/A' }}</strong></p>
        
        <form method="POST" action="#">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">New Email Address</label>
                <input type="email" 
                       name="new_email" 
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="newemail@example.com">
            </div>

            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Change Email
            </button>
        </form>
    </div>

    {{-- Password Tab --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Login Password (Optional)</h2>
        
        <p class="mb-4 text-sm opacity-70">
            You can set an optional password for quick login. OTP remains available as the primary method.
        </p>
        
        <form method="POST" action="#">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">New Password</label>
                <input type="password" 
                       name="password" 
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Confirm Password</label>
                <input type="password" 
                       name="password_confirmation" 
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);">
            </div>

            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Set Password
            </button>
        </form>
    </div>
</div>
@endsection
