@extends('layouts.guest')

@section('content')
{{-- Hero Section --}}
<div class="flex flex-col items-center justify-center min-h-[60vh] px-6 py-16">
    
    {{-- Hero: Big Logo --}}
    <div class="mb-10">
        <img src="{{ $platformLogo ?? '/images/branding/directdebit/logo.png' }}" 
             alt="DirectDebit.now" 
             class="w-auto mx-auto"
             style="height: 200px;">
    </div>

    {{-- Tagline: Multi-size All Caps --}}
    <div class="text-center mb-10">
        <p class="text-xl md:text-2xl font-medium tracking-widest opacity-70 mb-2 uppercase">
            {{ __translator('SEPA / INSTANT PAYMENTS') }}
        </p>
        <p class="text-4xl md:text-5xl font-bold tracking-wide uppercase" style="color: var(--brand-primary-color);">
            {{ __translator('DIRECT DEBIT PLATFORM') }}
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
                    <td class="font-semibold pr-4 align-top" style="width: 220px; color: #ffffff;">{{ __translator('Direct Debit Processing') }}</td>
                    <td class="opacity-70">{{ __translator('SEPA Direct Debit (SDD) Core and B2B schemes for automated recurring and one-time payments across Europe') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 align-top" style="color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="color: #ffffff;">{{ __translator('Instant Payments') }}</td>
                    <td class="opacity-70">{{ __translator('SEPA Instant Credit Transfers (SCT Inst) for real-time payment execution within 10 seconds') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 align-top" style="color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="color: #ffffff;">{{ __translator('Account Management') }}</td>
                    <td class="opacity-70">{{ __translator('Multi-currency ledgers, virtual IBANs, and segregated account structures for personal and business accounts') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 align-top" style="color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="color: #ffffff;">{{ __translator('Batch Processing') }}</td>
                    <td class="opacity-70">{{ __translator('High-volume batch payment processing with automated validation, scheduling, and reporting') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 align-top" style="color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="color: #ffffff;">{{ __translator('Compliance & KYC') }}</td>
                    <td class="opacity-70">{{ __translator('Integrated identity verification, AML screening, and transaction monitoring for regulatory compliance') }}</td>
                </tr>
                <tr>
                    <td class="pr-6 align-top" style="color: #ffffff;">✓</td>
                    <td class="font-semibold pr-4 align-top" style="color: #ffffff;">{{ __translator('API Integration') }}</td>
                    <td class="opacity-70">{{ __translator('RESTful API with comprehensive documentation for seamless integration into your platform') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Core Services Section --}}
<div class="px-6 py-16" style="border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-12" style="color: var(--brand-primary-color);">
            {{ __translator('CORE SERVICES') }}
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Payment Services --}}
            <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                <svg class="w-12 h-12 mb-4" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="font-bold mb-3 text-lg" style="color: var(--brand-primary-color);">{{ __translator('Payment Processing') }}</h3>
                <ul class="space-y-2 text-sm opacity-70">
                    <li>• {{ __translator('SEPA Direct Debit (Core & B2B)') }}</li>
                    <li>• {{ __translator('SEPA Credit Transfers') }}</li>
                    <li>• {{ __translator('Instant Payments (SCT Inst)') }}</li>
                    <li>• {{ __translator('Batch Payment Files') }}</li>
                    <li>• {{ __translator('Standing Orders') }}</li>
                </ul>
            </div>

            {{-- Account Services --}}
            <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                <svg class="w-12 h-12 mb-4" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="font-bold mb-3 text-lg" style="color: var(--brand-primary-color);">{{ __translator('Account Management') }}</h3>
                <ul class="space-y-2 text-sm opacity-70">
                    <li>• {{ __translator('Personal & Business Accounts') }}</li>
                    <li>• {{ __translator('Virtual IBAN Issuance') }}</li>
                    <li>• {{ __translator('Multi-Currency Ledgers') }}</li>
                    <li>• {{ __translator('Account Holder Verification') }}</li>
                    <li>• {{ __translator('Segregated Account Structures') }}</li>
                </ul>
            </div>

            {{-- Compliance Services --}}
            <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                <svg class="w-12 h-12 mb-4" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <h3 class="font-bold mb-3 text-lg" style="color: var(--brand-primary-color);">{{ __translator('Compliance & Security') }}</h3>
                <ul class="space-y-2 text-sm opacity-70">
                    <li>• {{ __translator('KYC/AML Verification') }}</li>
                    <li>• {{ __translator('Transaction Monitoring') }}</li>
                    <li>• {{ __translator('Bank Account Validation') }}</li>
                    <li>• {{ __translator('PSD2 Compliance') }}</li>
                    <li>• {{ __translator('Fraud Detection') }}</li>
                </ul>
            </div>
        </div>
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
                    <svg class="w-8 h-8 mb-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <h3 class="font-bold mb-2" style="color: var(--brand-primary-color);">{{ __translator('Subscription Businesses') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Automated recurring billing for SaaS, media, and membership services') }}</p>
                </div>
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <svg class="w-8 h-8 mb-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <h3 class="font-bold mb-2" style="color: var(--brand-primary-color);">{{ __translator('E-commerce Platforms') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Payment processing and payout distribution for marketplaces and online stores') }}</p>
                </div>
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <svg class="w-8 h-8 mb-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="font-bold mb-2" style="color: var(--brand-primary-color);">{{ __translator('FinTech Companies') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Banking-as-a-service infrastructure for digital banks and financial apps') }}</p>
                </div>
            </div>
            
            {{-- Right Column --}}
            <div class="space-y-6">
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <svg class="w-8 h-8 mb-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="font-bold mb-2" style="color: var(--brand-primary-color);">{{ __translator('Accounting Platforms') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Automated payment collection and reconciliation for accounting software') }}</p>
                </div>
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <svg class="w-8 h-8 mb-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h3 class="font-bold mb-2" style="color: var(--brand-primary-color);">{{ __translator('Payroll Services') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Bulk payment distribution for salary and vendor payments') }}</p>
                </div>
                <div class="p-6 rounded-lg" style="border: 1px solid rgba(255,255,255,0.1);">
                    <svg class="w-8 h-8 mb-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <h3 class="font-bold mb-2" style="color: var(--brand-primary-color);">{{ __translator('Utility Providers') }}</h3>
                    <p class="opacity-70 text-sm">{{ __translator('Automated billing and payment collection for telecom, energy, and utilities') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Key Features Section --}}
<div class="px-6 py-16" style="border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-12" style="color: var(--brand-primary-color);">
            {{ __translator('KEY FEATURES') }}
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background-color: var(--brand-primary-color);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">{{ __translator('Real-Time Processing') }}</h4>
                    <p class="text-sm opacity-70">{{ __translator('Instant payment execution and status updates via webhooks') }}</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background-color: var(--brand-primary-color);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">{{ __translator('Multi-Currency Support') }}</h4>
                    <p class="text-sm opacity-70">{{ __translator('EUR, GBP, USD, and other major currencies with FX conversion') }}</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background-color: var(--brand-primary-color);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">{{ __translator('Comprehensive Reporting') }}</h4>
                    <p class="text-sm opacity-70">{{ __translator('Detailed transaction logs, reconciliation files, and analytics dashboards') }}</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background-color: var(--brand-primary-color);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">{{ __translator('Developer-Friendly API') }}</h4>
                    <p class="text-sm opacity-70">{{ __translator('RESTful API with SDKs, sandbox environment, and detailed documentation') }}</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background-color: var(--brand-primary-color);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">{{ __translator('Bank-Grade Security') }}</h4>
                    <p class="text-sm opacity-70">{{ __translator('PCI-DSS compliant with 256-bit encryption and two-factor authentication') }}</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background-color: var(--brand-primary-color);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">{{ __translator('White-Label Ready') }}</h4>
                    <p class="text-sm opacity-70">{{ __translator('Fully customizable branding and user experience for your platform') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CTA Section --}}
<div class="px-6 py-20" style="border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="max-w-2xl mx-auto text-center">
        <h2 class="text-3xl font-bold mb-6" style="color: var(--brand-primary-color);">
            {{ __translator('READY TO GET STARTED?') }}
        </h2>
        <p class="text-lg opacity-70 mb-8">
            {{ __translator('Join the platform and start processing direct debit payments today') }}
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/login-register" 
               class="px-8 py-3 rounded-md font-semibold text-lg transition-all hover:opacity-90"
               style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Create Account') }}
            </a>
            <a href="/support" 
               class="px-8 py-3 rounded-md font-semibold text-lg transition-all hover:opacity-90"
               style="border: 2px solid var(--brand-primary-color); color: var(--brand-primary-color);">
                {{ __translator('Contact Sales') }}
            </a>
        </div>
    </div>
</div>

@endsection
