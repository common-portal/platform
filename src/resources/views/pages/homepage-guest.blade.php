@extends('layouts.guest')

@section('content')
{{-- Hero Section --}}
<div class="flex flex-col items-center justify-center min-h-[60vh] px-6 py-16">
    
    {{-- Hero: Big Logo --}}
    <div class="mb-10">
        <img src="{{ $platformLogo ?? '/images/platform-defaults/platform-logo.png' }}" 
             alt="{{ $platformName ?? 'Common Portal' }}" 
             class="w-auto mx-auto"
             style="height: 200px;">
    </div>

    {{-- Tagline: Multi-size All Caps --}}
    <div class="text-center mb-10">
        <p class="text-xl md:text-2xl font-medium tracking-widest opacity-70 mb-2 uppercase">
            {{ __translator('WHOLESALE / HIGH-VOLUME') }}
        </p>
        <p class="text-4xl md:text-5xl font-bold tracking-wide uppercase" style="color: var(--brand-primary-color);">
            {{ __translator('FIAT<>CRYPTO RAMP') }}
        </p>
    </div>

</div>

{{-- What We Do Section --}}
<div class="px-6 py-16" style="border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-12" style="color: var(--brand-primary-color);">
            {{ __translator('WHAT WE DO') }}
        </h2>
        
        <table class="w-full text-left" style="border-collapse: separate; border-spacing: 0 1rem;">
            <tbody>
                <tr>
                    <td class="pr-6 align-top" style="width: 40px; color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="width: 180px; color: #ffffff;">{{ __translator('Fiat to Crypto') }}</td>
                    <td class="opacity-70">{{ __translator('Convert EUR, GBP, USD and other fiat currencies to stablecoin (EURC, GBPC, USDT, etc.) and major cryptocurrencies (BTC, ETH, XRP, etc.)') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 align-top" style="color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="color: #ffffff;">{{ __translator('Crypto to Fiat') }}</td>
                    <td class="opacity-70">{{ __translator('Liquidate cryptocurrency holdings to fiat with competitive rates and fast settlement') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 align-top" style="color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="color: #ffffff;">{{ __translator('High Volume') }}</td>
                    <td class="opacity-70">{{ __translator('Manage large transactions from €50,000 to €10,000,000+ per order') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 align-top" style="color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="color: #ffffff;">{{ __translator('Fast Settlement') }}</td>
                    <td class="opacity-70">{{ __translator('Same-day or next-day settlement for verified partners') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Who We Serve Section --}}
<div class="px-6 py-16" style="border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-12" style="color: var(--brand-primary-color);">
            {{ __translator('WHO WE SERVE') }}
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Left Column --}}
            <div class="space-y-6">
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <span class="iconify text-3xl mb-3 block" data-icon="mdi:swap-horizontal-circle" style="color: #e3be3b;"></span>
                    <h3 class="font-bold mb-2" style="color: #4ade80;">{{ __translator('Crypto Exchanges') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Liquidity provision and fiat rails for trading platforms') }}</p>
                </div>
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <span class="iconify text-3xl mb-3 block" data-icon="mdi:handshake" style="color: #e3be3b;"></span>
                    <h3 class="font-bold mb-2" style="color: #4ade80;">{{ __translator('OTC Desks') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Wholesale rates for over-the-counter trading operations') }}</p>
                </div>
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <span class="iconify text-3xl mb-3 block" data-icon="mdi:credit-card-sync" style="color: #e3be3b;"></span>
                    <h3 class="font-bold mb-2" style="color: #4ade80;">{{ __translator('Payment Processors') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Backend crypto/fiat conversion for payment service providers') }}</p>
                </div>
            </div>
            
            {{-- Right Column --}}
            <div class="space-y-6">
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <span class="iconify text-3xl mb-3 block" data-icon="mdi:bank" style="color: #e3be3b;"></span>
                    <h3 class="font-bold mb-2" style="color: #4ade80;">{{ __translator('Funds & Treasuries') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Institutional-grade execution for crypto fund managers') }}</p>
                </div>
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <span class="iconify text-3xl mb-3 block" data-icon="mdi:pickaxe" style="color: #e3be3b;"></span>
                    <h3 class="font-bold mb-2" style="color: #4ade80;">{{ __translator('Mining Operations') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Efficient fiat conversion for mining revenue') }}</p>
                </div>
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <span class="iconify text-3xl mb-3 block" data-icon="mdi:office-building" style="color: #e3be3b;"></span>
                    <h3 class="font-bold mb-2" style="color: #4ade80;">{{ __translator('Corporate Treasury') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Bitcoin/crypto treasury management for businesses') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- How It Works Section --}}
<div class="px-6 py-16" style="border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-12" style="color: var(--brand-primary-color);">
            {{ __translator('HOW IT WORKS') }}
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
            <div class="p-6">
                <div class="text-4xl font-bold mb-4" style="color: var(--brand-primary-color);">1</div>
                <h3 class="font-semibold mb-2">{{ __translator('Register') }}</h3>
                <p class="text-sm opacity-70">{{ __translator('Create account & complete KYB verification') }}</p>
            </div>
            <div class="p-6">
                <div class="text-4xl font-bold mb-4" style="color: var(--brand-primary-color);">2</div>
                <h3 class="font-semibold mb-2">{{ __translator('Quote') }}</h3>
                <p class="text-sm opacity-70">{{ __translator('Request a quote for your exchange(s)') }}</p>
            </div>
            <div class="p-6">
                <div class="text-4xl font-bold mb-4" style="color: var(--brand-primary-color);">3</div>
                <h3 class="font-semibold mb-2">{{ __translator('Transfer') }}</h3>
                <p class="text-sm opacity-70">{{ __translator('Send fiat or crypto to execute') }}</p>
            </div>
            <div class="p-6">
                <div class="text-4xl font-bold mb-4" style="color: var(--brand-primary-color);">4</div>
                <h3 class="font-semibold mb-2">{{ __translator('Settle') }}</h3>
                <p class="text-sm opacity-70">{{ __translator('Receive funds same or next day') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- CTA Section --}}
<div class="px-6 py-16 text-center" style="border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold mb-4">{{ __translator('Ready to get started?') }}</h2>
        <p class="opacity-70 mb-8">{{ __translator('Register for a wholesale account and start converting at scale today.') }}</p>
        <a href="/login-register" 
           class="inline-block text-lg font-bold rounded-md transition-colors hover:opacity-90"
           style="background-color: var(--brand-primary-color); color: var(--button-text-color); padding: 20px 60px;">
            {{ __translator('REGISTER NOW') }}
        </a>
    </div>
</div>

@endsection
