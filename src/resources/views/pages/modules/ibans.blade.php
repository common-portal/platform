@extends('layouts.platform')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-2">{{ __translator('IBANs') }}</h1>
        <p class="opacity-70">{{ __translator('View your IBAN accounts grouped by currency') }}</p>
    </div>

    @if(session('error'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
        {{ session('error') }}
    </div>
    @endif

    @if($ibansByCurrency->isEmpty())
    {{-- No IBANs Found --}}
    <div class="rounded-lg p-12 text-center" style="background-color: var(--card-background-color);">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        <h3 class="text-lg font-semibold mb-2">{{ __translator('No IBANs Found') }}</h3>
        <p class="opacity-70">{{ __translator('No IBAN accounts have been set up for this account yet.') }}</p>
    </div>
    @else
    {{-- IBANs Grouped by Currency --}}
    <div class="space-y-6">
        @foreach($ibansByCurrency as $currency => $ibans)
        <div class="rounded-lg overflow-hidden" style="background-color: var(--card-background-color);">
            {{-- Currency Card Header --}}
            <div class="px-6 py-4 border-b" style="border-color: var(--sidebar-hover-background-color); background-color: var(--content-background-color);">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <h2 class="text-xl font-bold">{{ $currency }}</h2>
                    </div>
                    <span class="text-sm opacity-70">{{ $ibans->count() }} {{ $ibans->count() === 1 ? __translator('Account') : __translator('Accounts') }}</span>
                </div>
            </div>

            {{-- IBAN Records Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('Created') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('Name') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('IBAN Number') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('Status') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('Balance') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--sidebar-hover-background-color);">
                        @foreach($ibans as $iban)
                        <tr class="hover:opacity-80 transition-opacity" style="background-color: var(--card-background-color);">
                            {{-- Created Date --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $iban->datetime_created?->format('M j, Y') }}
                            </td>
                            
                            {{-- Friendly Name --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium">{{ $iban->iban_friendly_name }}</div>
                            </td>
                            
                            {{-- IBAN Number --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-mono opacity-90">{{ $iban->iban_number }}</div>
                            </td>
                            
                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($iban->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                      style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                                    {{ __translator('Active') }}
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                      style="background-color: #f59e0b; color: #1a1a2e;">
                                    {{ __translator('Paused') }}
                                </span>
                                @endif
                            </td>
                            
                            {{-- Balance (placeholder for future API integration) --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-mono font-medium">
                                    <span class="opacity-50">—</span>
                                </div>
                                <div class="text-xs opacity-50">{{ __translator('API pending') }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Currency Card Footer (Summary) --}}
            <div class="px-6 py-3 border-t" style="border-color: var(--sidebar-hover-background-color); background-color: var(--content-background-color);">
                <div class="flex items-center justify-between text-sm">
                    <span class="opacity-70">
                        {{ __translator('Total') }} {{ $currency }} {{ __translator('Accounts') }}: <span class="font-medium">{{ $ibans->count() }}</span>
                    </span>
                    <span class="opacity-70">
                        {{ __translator('Active') }}: <span class="font-medium">{{ $ibans->where('is_active', true)->count() }}</span> | 
                        {{ __translator('Paused') }}: <span class="font-medium">{{ $ibans->where('is_active', false)->count() }}</span>
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Info Notice --}}
    <div class="mt-6 rounded-lg p-4" style="background-color: var(--content-background-color); border-left: 4px solid var(--brand-primary-color);">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm">
                <p class="font-medium mb-1">{{ __translator('Real-Time Balance Integration') }}</p>
                <p class="opacity-70">{{ __translator('IBAN balances will be fetched in real-time from host bank APIs. This feature is currently under development.') }}</p>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
