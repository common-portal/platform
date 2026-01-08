@extends('layouts.platform')

@section('content')
{{-- Member Settings Page with Tabs --}}

<div class="max-w-2xl mx-auto" x-data="{ activeTab: 'profile' }">
    <h1 class="text-2xl font-bold mb-6">{{ __translator('My Profile') }}</h1>

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

    {{-- Tab Navigation --}}
    <div class="flex space-x-1 mb-6 border-b" style="border-color: var(--sidebar-hover-background-color);">
        <button @click="activeTab = 'profile'" 
                :class="activeTab === 'profile' ? 'border-b-2' : 'opacity-60 hover:opacity-100'"
                :style="activeTab === 'profile' ? 'border-color: var(--brand-primary-color); color: var(--brand-primary-color)' : ''"
                class="px-4 py-3 text-sm font-medium transition-all">
            {{ __translator('Profile') }}
        </button>
        <button @click="activeTab = 'email'" 
                :class="activeTab === 'email' ? 'border-b-2' : 'opacity-60 hover:opacity-100'"
                :style="activeTab === 'email' ? 'border-color: var(--brand-primary-color); color: var(--brand-primary-color)' : ''"
                class="px-4 py-3 text-sm font-medium transition-all">
            {{ __translator('Login Email') }}
        </button>
        <button @click="activeTab = 'password'" 
                :class="activeTab === 'password' ? 'border-b-2' : 'opacity-60 hover:opacity-100'"
                :style="activeTab === 'password' ? 'border-color: var(--brand-primary-color); color: var(--brand-primary-color)' : ''"
                class="px-4 py-3 text-sm font-medium transition-all">
            {{ __translator('Login Password') }}
        </button>
    </div>

    {{-- Tab Content --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        
        {{-- Profile Tab --}}
        <div x-show="activeTab === 'profile'" x-cloak>
            <form method="POST" action="{{ route('member.settings.profile') }}">
                @csrf
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __translator('First Name') }}</label>
                        <input type="text" 
                               name="member_first_name" 
                               value="{{ old('member_first_name', auth()->user()->member_first_name) }}"
                               class="w-full px-4 py-2 rounded-md border-0"
                               style="background-color: var(--content-background-color); color: var(--content-text-color);">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __translator('Last Name') }}</label>
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
                    {{ __translator('Update Profile') }}
                </button>
            </form>
        </div>

        {{-- Login Email Tab --}}
        <div x-show="activeTab === 'email'" x-cloak>
            <p class="mb-4 opacity-70">{{ __translator('Current email') }}: <strong>{{ auth()->user()->login_email_address }}</strong></p>
            
            <form method="POST" action="{{ route('member.settings.email') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">{{ __translator('New Email Address') }}</label>
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
                    {{ __translator('Change Email') }}
                </button>
            </form>
        </div>

        {{-- Login Password Tab --}}
        <div x-show="activeTab === 'password'" x-cloak>
            @if(session('password_status'))
            <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
                {{ session('password_status') }}
            </div>
            @endif

            <p class="mb-4 text-sm opacity-70">
                @if(auth()->user()->hashed_login_password)
                    {{ __translator('You have a password set. You can update it or remove it below.') }}
                @else
                    {{ __translator('Set an optional password for quick login. OTP remains available as the primary method.') }}
                @endif
            </p>
            
            <form method="POST" action="{{ route('member.settings.password') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">{{ auth()->user()->hashed_login_password ? __translator('New Password') : __translator('Password') }}</label>
                    <input type="password" 
                           name="password" 
                           class="w-full px-4 py-2 rounded-md border-0"
                           style="background-color: var(--content-background-color); color: var(--content-text-color);"
                           minlength="8"
                           required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">{{ __translator('Confirm Password') }}</label>
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
                        {{ auth()->user()->hashed_login_password ? __translator('Update Password') : __translator('Set Password') }}
                    </button>
                    
                    @if(auth()->user()->hashed_login_password)
                    <button type="button"
                            onclick="if(confirm('{{ __translator('Remove password? You can still log in with OTP.') }}')) document.getElementById('remove-password-form').submit();"
                            class="px-6 py-2 rounded-md font-medium"
                            style="background-color: var(--status-error-color); color: white;">
                        {{ __translator('Remove Password') }}
                    </button>
                    @endif
                </div>
            </form>
            
            <form id="remove-password-form" method="POST" action="{{ route('member.settings.password.remove') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
