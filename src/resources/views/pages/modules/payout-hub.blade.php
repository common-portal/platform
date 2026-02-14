@extends('layouts.platform')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">{{ __translator('Payout') }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Crypto Payout --}}
        <a href="{{ route('modules.payout.crypto') }}" 
           class="rounded-lg p-6 hover:opacity-80 transition-opacity"
           style="background-color: var(--card-background-color); border: 1px solid var(--card-border-color);">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: rgba(124,58,237,0.2);">
                    <svg class="w-6 h-6" style="color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold">{{ __translator('Payout Crypto') }}</h2>
                    <p class="text-sm opacity-70">{{ __translator('Send crypto from your wallet to an external address') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-sm" style="color: var(--brand-primary-color);">
                <span>{{ __translator('Start payout') }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        {{-- Fiat Payout --}}
        <a href="{{ route('modules.payout.fiat') }}" 
           class="rounded-lg p-6 hover:opacity-80 transition-opacity"
           style="background-color: var(--card-background-color); border: 1px solid var(--card-border-color);">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: rgba(245,158,11,0.2);">
                    <svg class="w-6 h-6" style="color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold">{{ __translator('Payout Fiat') }}</h2>
                    <p class="text-sm opacity-70">{{ __translator('Send fiat from your IBAN via SEPA or SWIFT') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-sm" style="color: var(--brand-primary-color);">
                <span>{{ __translator('Start payout') }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>
</div>
@endsection
