@extends('layouts.platform')

@section('content')
<div class="max-w-4xl mx-auto">
    
    {{-- Welcome Card --}}
    <div class="rounded-lg p-6 mb-2" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-2">
            {{ __translator('Welcome back,') }} {{ auth()->user()->member_first_name ?: __translator('there') }}!
        </h1>
        <p class="opacity-70">
            {{ __translator('Select an account from the sidebar to get started.') }}
        </p>
    </div>

    {{-- Brand Name, Logo & Powered By --}}
    <div class="flex flex-col items-center justify-center pt-10 pb-0">
        @php
            $logoPath = \App\Models\PlatformSetting::getValue('platform_logo_image_path');
            $platformName = \App\Models\PlatformSetting::getValue('platform_display_name', 'Common Portal');
        @endphp
        
        {{-- Brand Name --}}
        <h2 class="font-bold text-white mb-8" style="font-size: 52px;">{{ $platformName }}</h2>
        
        {{-- Logo --}}
        @if($logoPath)
            <img src="{{ Storage::url($logoPath) }}" 
                 alt="{{ $platformName }}" 
                 class="w-auto mb-10"
                 style="height: 333px;">
        @else
            {{-- Default Platform Logo --}}
            <img src="/images/platform-defaults/platform-logo.png" 
                 alt="{{ $platformName }}" 
                 class="w-auto mb-8"
                 style="height: 300px;">
        @endif
        
        {{-- Powered By --}}
        <p class="text-xl opacity-60 mt-6">
            Powered by <a href="https://nsdb.com/?tab=services" target="_NSDB" class="hover:opacity-80" style="color: var(--brand-primary-color);">NSDB.COM</a>
        </p>
    </div>

    {{-- Account Overview --}}
    @if($activeAccount ?? null)
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">{{ __translator('Current Account') }}</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="opacity-60">{{ __translator('Account Name:') }}</span>
                <p class="font-medium">{{ $activeAccount->account_display_name }}</p>
            </div>
            <div>
                <span class="opacity-60">{{ __translator('Account Type:') }}</span>
                <p class="font-medium">{{ $activeAccount->account_type === 'personal_individual' ? __translator('Personal') : __translator('Business') }}</p>
            </div>
            <div>
                <span class="opacity-60">{{ __translator('Your Role:') }}</span>
                <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $memberRole ?? 'Member')) }}</p>
            </div>
            <div>
                <span class="opacity-60">{{ __translator('Team Members:') }}</span>
                <p class="font-medium">{{ $teamMemberCount ?? 1 }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
