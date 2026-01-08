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
            {{-- Avatar Upload --}}
            <div class="flex items-center space-x-6 mb-6">
                <div class="relative" x-data="{ preview: null }">
                    {{-- Avatar Preview --}}
                    <div class="w-24 h-24 rounded-full overflow-hidden flex items-center justify-center"
                         style="background-color: var(--content-background-color);">
                        @if(auth()->user()->profile_avatar_image_path)
                            <img src="{{ asset('storage/' . auth()->user()->profile_avatar_image_path) }}" 
                                 alt="{{ __translator('Profile Photo') }}"
                                 class="w-full h-full object-cover"
                                 x-show="!preview">
                        @else
                            <svg x-show="!preview" class="w-12 h-12 opacity-40" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        @endif
                        <img x-show="preview" :src="preview" class="w-full h-full object-cover">
                    </div>
                    
                    {{-- Upload Button Overlay --}}
                    <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 hover:opacity-100 transition-opacity cursor-pointer">
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
                    <p class="text-xs opacity-60 mb-2">{{ __translator('Click to upload (max 2MB)') }}</p>
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
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
