@extends('layouts.platform')

@section('content')
{{-- Member Settings Page with Tabs --}}

<div class="max-w-2xl mx-auto" x-data="{ activeTab: '{{ session('two_factor_status') || $errors->has('code') ? 'two-factor' : 'profile' }}' }">
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
    <div class="flex mb-6 rounded-lg overflow-hidden" style="background-color: var(--sidebar-hover-background-color); border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
        <button @click="activeTab = 'profile'" 
                :class="activeTab === 'profile' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-right: 1px solid rgba(255,255,255,0.15); border-bottom: 2px solid ' + (activeTab === 'profile' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'profile' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ __translator('Profile') }}
            </span>
        </button>
        <button @click="activeTab = 'email'" 
                :class="activeTab === 'email' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-right: 1px solid rgba(255,255,255,0.15); border-bottom: 2px solid ' + (activeTab === 'email' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'email' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                {{ __translator('Login Email') }}
            </span>
        </button>
        <button @click="activeTab = 'password'" 
                :class="activeTab === 'password' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-right: 1px solid rgba(255,255,255,0.15); border-bottom: 2px solid ' + (activeTab === 'password' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'password' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                {{ __translator('Login Password') }}
            </span>
        </button>
        <button @click="activeTab = 'two-factor'" 
                :class="activeTab === 'two-factor' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-bottom: 2px solid ' + (activeTab === 'two-factor' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'two-factor' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                {{ __translator('2FA') }}
            </span>
        </button>
    </div>

    {{-- Tab Content --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        
        {{-- Profile Tab --}}
        <div x-show="activeTab === 'profile'" x-cloak>
            {{-- Avatar Upload --}}
            <div class="flex items-center space-x-6 mb-6">
                <div class="relative" x-data="{ preview: null }" style="width: 80px; height: 80px; flex-shrink: 0;">
                    {{-- Avatar Preview --}}
                    <div class="rounded-lg overflow-hidden flex items-center justify-center"
                         style="width: 80px; height: 80px; background-color: var(--content-background-color);">
                        @if(auth()->user()->profile_avatar_image_path)
                            <img src="{{ asset('storage/' . auth()->user()->profile_avatar_image_path) }}" 
                                 alt="{{ __translator('Profile Photo') }}"
                                 style="max-width: 80px; max-height: 80px; object-fit: cover;"
                                 x-show="!preview">
                        @else
                            <svg x-show="!preview" class="w-10 h-10 opacity-40" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        @endif
                        <img x-show="preview" :src="preview" style="max-width: 80px; max-height: 80px; object-fit: cover;">
                    </div>
                    
                    {{-- Upload Button Overlay --}}
                    <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-lg opacity-0 hover:opacity-100 transition-opacity cursor-pointer">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <input type="file" 
                               class="hidden" 
                               accept="image/*"
                               form="avatar-form"
                               name="avatar"
                               @change="
                                   const file = $event.target.files[0];
                                   if (file) {
                                       preview = URL.createObjectURL(file);
                                       $refs.avatarSubmit.click();
                                   }
                               ">
                    </label>
                </div>
                
                <div>
                    <p class="text-sm font-medium mb-1">{{ __translator('Profile Photo') }}</p>
                    <p class="text-xs opacity-60 mb-2">{{ __translator('Click to upload (max 10MB)') }}</p>
                    @if(auth()->user()->profile_avatar_image_path)
                    <button type="button" 
                            onclick="if(confirm('{{ __translator('Remove profile photo?') }}')) document.getElementById('remove-avatar-form').submit();"
                            class="text-xs px-3 py-1 rounded"
                            style="background-color: var(--status-error-color); color: white;">
                        {{ __translator('Remove') }}
                    </button>
                    @endif
                </div>
            </div>
            
            {{-- Hidden Avatar Forms --}}
            <form id="avatar-form" method="POST" action="{{ route('member.settings.avatar') }}" enctype="multipart/form-data" class="hidden">
                @csrf
                <button type="submit" x-ref="avatarSubmit"></button>
            </form>
            <form id="remove-avatar-form" method="POST" action="{{ route('member.settings.avatar.remove') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>

            {{-- Profile Name Form --}}
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

        {{-- Two-Factor Authentication Tab --}}
        <div x-show="activeTab === 'two-factor'" x-cloak>
            @if(session('two_factor_status'))
            <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
                {{ session('two_factor_status') }}
            </div>
            @endif

            @if($errors->has('code'))
            <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
                {{ $errors->first('code') }}
            </div>
            @endif

            @if(auth()->user()->hasTwoFactorEnabled())
                {{-- State C: 2FA Active --}}
                <div class="flex items-center gap-3 mb-4 p-3 rounded-md" style="background-color: rgba(0, 255, 136, 0.1); border: 1px solid rgba(0, 255, 136, 0.3);">
                    <svg style="width: 20px; height: 20px; flex-shrink: 0; color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="text-sm font-medium" style="color: var(--brand-primary-color);">{{ __translator('Two-factor authentication is enabled.') }}</span>
                </div>

                <p class="mb-6 text-sm opacity-70">
                    {{ __translator('Your account is protected with an authenticator app. You will be asked for a 6-digit code each time you log in.') }}
                </p>

                <button type="button"
                        onclick="if(confirm('{{ __translator('Are you sure you want to disable two-factor authentication?') }}')) document.getElementById('disable-2fa-form').submit();"
                        class="px-6 py-2 rounded-md font-medium"
                        style="background-color: var(--status-error-color); color: white;">
                    {{ __translator('Disable 2FA') }}
                </button>

                <form id="disable-2fa-form" method="POST" action="{{ route('member.settings.two-factor.disable') }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>

            @elseif(auth()->user()->two_factor_secret_key)
                {{-- State B: Secret generated, awaiting confirmation --}}
                @php
                    $google2fa = app(\PragmaRX\Google2FALaravel\Google2FA::class);
                    $otpauthUrl = $google2fa->getQRCodeUrl(
                        config('app.name'),
                        auth()->user()->login_email_address,
                        auth()->user()->two_factor_secret_key
                    );
                @endphp

                <p class="mb-4 text-sm opacity-70">
                    {{ __translator('Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code below to activate.') }}
                </p>

                {{-- QR Code --}}
                <div class="flex justify-center mb-4">
                    <div id="two-factor-qr-code" class="p-4 rounded-lg" style="background-color: white;"></div>
                </div>

                {{-- Manual entry secret --}}
                <div class="mb-6 text-center">
                    <p class="text-xs opacity-50 mb-1">{{ __translator('Or enter this code manually:') }}</p>
                    <code class="text-sm font-mono px-3 py-1 rounded" style="background-color: var(--content-background-color);">{{ auth()->user()->two_factor_secret_key }}</code>
                </div>

                {{-- Confirmation form --}}
                <form method="POST" action="{{ route('member.settings.two-factor.confirm') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">{{ __translator('6-Digit Code') }}</label>
                        <input type="text" 
                               name="code" 
                               class="w-full px-4 py-2 rounded-md border-0 text-center text-xl tracking-widest"
                               style="background-color: var(--content-background-color); color: var(--content-text-color);"
                               maxlength="6"
                               pattern="[0-9]{6}"
                               placeholder="000000"
                               inputmode="numeric"
                               autocomplete="one-time-code"
                               autofocus
                               required>
                    </div>

                    <div class="flex space-x-3">
                        <button type="submit" 
                                class="px-6 py-2 rounded-md font-medium"
                                style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                            {{ __translator('Verify & Activate') }}
                        </button>

                        <button type="button"
                                onclick="if(confirm('{{ __translator('Cancel 2FA setup?') }}')) document.getElementById('cancel-2fa-form').submit();"
                                class="px-6 py-2 rounded-md font-medium opacity-70 hover:opacity-100">
                            {{ __translator('Cancel') }}
                        </button>
                    </div>
                </form>

                <form id="cancel-2fa-form" method="POST" action="{{ route('member.settings.two-factor.disable') }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>

                {{-- QR Code rendering via qrcodejs --}}
                <script src="https://cdn.jsdelivr.net/npm/davidshimjs-qrcodejs@0.0.2/qrcode.min.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        new QRCode(document.getElementById('two-factor-qr-code'), {
                            text: @json($otpauthUrl),
                            width: 200,
                            height: 200,
                            colorDark: '#000000',
                            colorLight: '#ffffff',
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    });
                </script>

            @else
                {{-- State A: 2FA Not Enabled --}}
                <p class="mb-4 text-sm opacity-70">
                    {{ __translator('Add an extra layer of security to your account by using an authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.).') }}
                </p>
                <p class="mb-6 text-sm opacity-70">
                    {{ __translator('When enabled, you will be asked for a 6-digit code from your authenticator app each time you log in.') }}
                </p>

                <form method="POST" action="{{ route('member.settings.two-factor.enable') }}">
                    @csrf
                    <button type="submit" 
                            class="px-6 py-2 rounded-md font-medium"
                            style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                        {{ __translator('Enable 2FA') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
