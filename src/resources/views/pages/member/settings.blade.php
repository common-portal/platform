@extends('layouts.platform')

@section('content')
{{-- Member Settings Page --}}

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">My Profile</h1>

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

    {{-- Profile Section --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Profile</h2>
        
        <form method="POST" action="{{ route('member.settings.profile') }}">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2">First Name</label>
                    <input type="text" 
                           name="member_first_name" 
                           value="{{ old('member_first_name', auth()->user()->member_first_name) }}"
                           class="w-full px-4 py-2 rounded-md border-0"
                           style="background-color: var(--content-background-color); color: var(--content-text-color);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Last Name</label>
                    <input type="text" 
                           name="member_last_name" 
                           value="{{ old('member_last_name', auth()->user()->member_last_name) }}"
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

    {{-- Login Email Section --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Login Email</h2>
        
        <p class="mb-4 opacity-70">Current email: <strong>{{ auth()->user()->login_email_address }}</strong></p>
        
        <form method="POST" action="{{ route('member.settings.email') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">New Email Address</label>
                <input type="email" 
                       name="new_email" 
                       value="{{ old('new_email') }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="newemail@example.com"
                       required>
            </div>

            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Change Email
            </button>
        </form>
    </div>

    {{-- Password Section --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Login Password (Optional)</h2>
        
        @if(session('password_status'))
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
            {{ session('password_status') }}
        </div>
        @endif

        <p class="mb-4 text-sm opacity-70">
            @if(auth()->user()->hashed_login_password)
                You have a password set. You can update it or remove it below.
            @else
                Set an optional password for quick login. OTP remains available as the primary method.
            @endif
        </p>
        
        <form method="POST" action="{{ route('member.settings.password') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ auth()->user()->hashed_login_password ? 'New Password' : 'Password' }}</label>
                <input type="password" 
                       name="password" 
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       minlength="8"
                       required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Confirm Password</label>
                <input type="password" 
                       name="password_confirmation" 
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       required>
            </div>

            <div class="flex space-x-3">
                <button type="submit" 
                        class="px-6 py-2 rounded-md font-medium"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    {{ auth()->user()->hashed_login_password ? 'Update Password' : 'Set Password' }}
                </button>
                
                @if(auth()->user()->hashed_login_password)
                <button type="button"
                        onclick="if(confirm('Remove password? You can still log in with OTP.')) document.getElementById('remove-password-form').submit();"
                        class="px-6 py-2 rounded-md font-medium"
                        style="background-color: var(--status-error-color); color: white;">
                    Remove Password
                </button>
                @endif
            </div>
        </form>
        
        <form id="remove-password-form" method="POST" action="{{ route('member.settings.password.remove') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

    {{-- Language Preference Section --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Language Preference</h2>
        
        @if(session('language_status'))
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
            {{ session('language_status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('member.settings.language') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Preferred Language</label>
                <select name="language_code" 
                        class="w-full px-4 py-2 rounded-md border-0"
                        style="background-color: var(--content-background-color); color: var(--content-text-color);">
                    <option value="en" {{ auth()->user()->preferred_language_code === 'en' ? 'selected' : '' }}>English</option>
                    <option value="es" {{ auth()->user()->preferred_language_code === 'es' ? 'selected' : '' }}>Español</option>
                    <option value="fr" {{ auth()->user()->preferred_language_code === 'fr' ? 'selected' : '' }}>Français</option>
                    <option value="de" {{ auth()->user()->preferred_language_code === 'de' ? 'selected' : '' }}>Deutsch</option>
                    <option value="pt" {{ auth()->user()->preferred_language_code === 'pt' ? 'selected' : '' }}>Português</option>
                </select>
            </div>

            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Save Language
            </button>
        </form>
    </div>
</div>
@endsection
