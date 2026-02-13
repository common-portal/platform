@extends('layouts.platform')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-2">{{ __translator('Fees') }}</h1>
        <p class="opacity-70">{{ __translator('View your account fees per currency') }}</p>
    </div>

    @if(session('error'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
        {{ session('error') }}
    </div>
    @endif

    @if(!$gbpFees && !$eurFees)
    {{-- No Fees Configured --}}
    <div class="rounded-lg p-12 text-center" style="background-color: var(--card-background-color);">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="text-lg font-semibold mb-2">{{ __translator('No Fees Configured') }}</h3>
        <p class="opacity-70">{{ __translator('No fee schedules have been set up for this account yet. Please contact your administrator.') }}</p>
    </div>
    @else
    {{-- Fee Cards by Currency --}}
    <div class="space-y-6">

        {{-- GBP Fees Card --}}
        @if($gbpFees)
        <div class="rounded-lg overflow-hidden" style="background-color: var(--card-background-color);">
            <div class="px-6 py-4 border-b" style="border-color: var(--sidebar-hover-background-color); background-color: var(--content-background-color);">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-xl font-bold">GBP</h2>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-lg p-4" style="background-color: var(--content-background-color);">
                        <p class="text-xs uppercase tracking-wide opacity-60 mb-1">{{ __translator('Fixed Fee') }}</p>
                        <p class="text-2xl font-bold font-mono">&pound;{{ number_format($gbpFees->fixed_fee, 2) }}</p>
                        <p class="text-xs opacity-50 mt-1">{{ __translator('Flat fee per transaction') }}</p>
                    </div>
                    <div class="rounded-lg p-4" style="background-color: var(--content-background-color);">
                        <p class="text-xs uppercase tracking-wide opacity-60 mb-1">{{ __translator('Percentage Fee') }}</p>
                        <p class="text-2xl font-bold font-mono">{{ number_format($gbpFees->percentage_fee, 2) }}%</p>
                        <p class="text-xs opacity-50 mt-1">{{ __translator('Percentage of transaction amount') }}</p>
                    </div>
                    <div class="rounded-lg p-4" style="background-color: var(--content-background-color);">
                        <p class="text-xs uppercase tracking-wide opacity-60 mb-1">{{ __translator('Minimum Fee') }}</p>
                        <p class="text-2xl font-bold font-mono">&pound;{{ number_format($gbpFees->minimum_fee, 2) }}</p>
                        <p class="text-xs opacity-50 mt-1">{{ __translator('Minimum charge per transaction') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- EUR Fees Card --}}
        @if($eurFees)
        <div class="rounded-lg overflow-hidden" style="background-color: var(--card-background-color);">
            <div class="px-6 py-4 border-b" style="border-color: var(--sidebar-hover-background-color); background-color: var(--content-background-color);">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-xl font-bold">EUR</h2>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-lg p-4" style="background-color: var(--content-background-color);">
                        <p class="text-xs uppercase tracking-wide opacity-60 mb-1">{{ __translator('Fixed Fee') }}</p>
                        <p class="text-2xl font-bold font-mono">&euro;{{ number_format($eurFees->fixed_fee, 2) }}</p>
                        <p class="text-xs opacity-50 mt-1">{{ __translator('Flat fee per transaction') }}</p>
                    </div>
                    <div class="rounded-lg p-4" style="background-color: var(--content-background-color);">
                        <p class="text-xs uppercase tracking-wide opacity-60 mb-1">{{ __translator('Percentage Fee') }}</p>
                        <p class="text-2xl font-bold font-mono">{{ number_format($eurFees->percentage_fee, 2) }}%</p>
                        <p class="text-xs opacity-50 mt-1">{{ __translator('Percentage of transaction amount') }}</p>
                    </div>
                    <div class="rounded-lg p-4" style="background-color: var(--content-background-color);">
                        <p class="text-xs uppercase tracking-wide opacity-60 mb-1">{{ __translator('Minimum Fee') }}</p>
                        <p class="text-2xl font-bold font-mono">&euro;{{ number_format($eurFees->minimum_fee, 2) }}</p>
                        <p class="text-xs opacity-50 mt-1">{{ __translator('Minimum charge per transaction') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Info Notice --}}
    <div class="mt-6 rounded-lg p-4" style="background-color: var(--content-background-color); border-left: 4px solid var(--brand-primary-color);">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm">
                <p class="font-medium mb-1">{{ __translator('Fee Schedule') }}</p>
                <p class="opacity-70">{{ __translator('These are the fees applied to transactions on your account. The greater of the percentage fee or the minimum fee will be charged, plus any fixed fee. Contact your administrator if you have questions about your fee schedule.') }}</p>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
