@extends('layouts.platform')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>
<div class="max-w-7xl mx-auto" x-data="{ 
    showToast: false,
    toastMessage: ''
}" @copy-value.window="toastMessage = $event.detail; showToast = true; setTimeout(() => showToast = false, 3000)">
    
    {{-- Toast Notification --}}
    <div x-cloak
         x-show="showToast" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed top-4 right-4 z-50 max-w-md rounded-lg p-4 shadow-lg" 
         style="background-color: #10b981; color: white;">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <span class="font-medium">COPIED:</span><br>
                <span class="text-sm font-mono" x-text="toastMessage"></span>
            </div>
        </div>
    </div>
    
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
                        <tr class="text-left text-sm border-b" style="border-color: var(--sidebar-hover-background-color); color: var(--status-warning-color);">
                            <th style="width: 30px;"></th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('Created') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('Name') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('IBAN Number') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('Balance') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider opacity-70">
                                {{ __translator('Status') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ibans as $iban)
                        @php
                            $rowClass = $loop->odd ? 'background-color: rgba(255, 255, 255, 0.02);' : '';
                        @endphp
                        <tbody x-data="{ expanded: false, copySuccess: {} }" class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                        <tr style="{{ $rowClass }}">
                            {{-- Expand Icon --}}
                            <td class="px-3 py-4 cursor-pointer" @click="expanded = !expanded" style="width: 30px;">
                                <svg class="w-4 h-4 transition-transform duration-200" :style="expanded ? 'transform: rotate(90deg)' : 'transform: rotate(0deg)'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </td>
                            
                            {{-- Created Date --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm cursor-pointer" @click="expanded = !expanded">
                                {{ $iban->datetime_created?->format('M j, Y') }}
                            </td>
                            
                            {{-- Friendly Name --}}
                            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" @click="expanded = !expanded">
                                <div class="text-sm font-medium">{{ $iban->iban_friendly_name }}</div>
                            </td>
                            
                            {{-- IBAN Number --}}
                            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" @click="expanded = !expanded">
                                <div class="text-sm font-mono opacity-90">{{ $iban->iban_number }}</div>
                            </td>
                            
                            {{-- Balance --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right cursor-pointer" @click="expanded = !expanded">
                                @if($iban->balance !== null)
                                <div class="text-sm font-medium" style="color: var(--brand-primary-color);">
                                    {{ number_format($iban->balance, 2) }}
                                </div>
                                @else
                                <div class="text-sm opacity-40">-</div>
                                @endif
                            </td>
                            
                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" @click="expanded = !expanded">
                                @if($iban->is_active)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium" 
                                      style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                                    {{ __translator('Active') }}
                                </span>
                                @else
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium" 
                                      style="background-color: #f59e0b; color: #1a1a2e;">
                                    {{ __translator('Paused') }}
                                </span>
                                @endif
                            </td>
                        </tr>
                        
                        {{-- Expanded Row with Details --}}
                        <tr x-show="expanded" style="display: none; {{ $rowClass }}">
                            <td colspan="6" class="p-0">
                                <div class="px-12 py-6">
                                    <h3 class="text-sm font-semibold mb-4 pb-2 border-b ml-4" style="color: var(--brand-primary-color); border-color: var(--sidebar-hover-background-color);">IBAN DETAILS</h3>
                                    <div class="rounded-lg p-4 space-y-1 text-sm" style="background-color: rgba(255, 255, 255, 0.03);">
                                        @php
                                            $ibanFields = [
                                                ['label' => 'IBAN Friendly Name', 'value' => $iban->iban_friendly_name],
                                                ['label' => 'Currency', 'value' => $iban->iban_currency_iso3],
                                                ['label' => 'IBAN Number', 'value' => $iban->iban_number],
                                                ['label' => 'BIC Routing', 'value' => $iban->bic_routing],
                                                ['label' => 'IBAN Owner', 'value' => $iban->iban_owner],
                                                ['label' => 'Status', 'value' => $iban->is_active ? 'Active' : 'Paused'],
                                                ['label' => 'Created', 'value' => $iban->datetime_created ? $iban->datetime_created->format('M j, Y H:i') : null],
                                            ];
                                        @endphp
                                        
                                        @foreach($ibanFields as $field)
                                        <div class="flex items-center py-2 border-b border-opacity-30" style="border-color: var(--sidebar-hover-background-color);">
                                            <div class="w-1/2 opacity-60">{{ $field['label'] }}:</div>
                                            <div class="w-1/2 flex items-center justify-between">
                                                <span class="font-mono flex-1">{{ $field['value'] ?? '-' }}</span>
                                                @if($field['value'])
                                                <button @click="navigator.clipboard.writeText('{{ $field['value'] }}'); window.dispatchEvent(new CustomEvent('copy-value', { detail: '{{ $field['value'] }}' })); copySuccess['{{ Str::slug($field['label']) }}'] = true; setTimeout(() => copySuccess['{{ Str::slug($field['label']) }}'] = false, 2000)" class="ml-2 opacity-40 hover:opacity-100 transition-opacity">
                                                    <svg class="w-3.5 h-3.5" :class="{ 'text-green-400': copySuccess['{{ Str::slug($field['label']) }}'] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                </button>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </tbody>
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
    @endif

</div>
@endsection
