@extends('layouts.platform')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>
{{-- Transactions History Module --}}

<div class="max-w-4xl mx-auto" x-data="{ 
    showAdvanced: false,
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

    <h1 class="text-2xl font-bold mb-6">Transaction History</h1>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    {{-- Search/Filter Card --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: #333333;">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Search Transactions</h2>
        
        <form method="GET" action="{{ route('modules.transactions') }}">
            {{-- Basic Search Fields (Always Visible) --}}
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Received Amount</label>
                    <input type="number" step="0.00001" name="received_amount" value="{{ request('received_amount') }}" 
                           placeholder="e.g., 100000" 
                           class="w-full px-3 py-2 rounded-md text-sm" 
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Received Currency</label>
                    <select name="received_currency" class="w-full px-3 py-2 rounded-md text-sm" 
                            style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        <option value="">All Currencies</option>
                        <option value="EUR" {{ request('received_currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                        <option value="GBP" {{ request('received_currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                        <option value="USD" {{ request('received_currency') == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="AUD" {{ request('received_currency') == 'AUD' ? 'selected' : '' }}>AUD</option>
                        <option value="CAD" {{ request('received_currency') == 'CAD' ? 'selected' : '' }}>CAD</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Date Received (From)</label>
                    <input type="date" name="date_received_from" value="{{ request('date_received_from') }}" 
                           class="w-full px-3 py-2 rounded-md text-sm" 
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Date Received (Through)</label>
                    <input type="date" name="date_received_through" value="{{ request('date_received_through') }}" 
                           class="w-full px-3 py-2 rounded-md text-sm" 
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Transaction Hash</label>
                <input type="text" name="transaction_id" value="{{ request('transaction_id') }}" 
                       placeholder="Enter transaction hash" 
                       class="w-full px-3 py-2 rounded-md text-sm font-mono" 
                       style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
            </div>

            {{-- Advanced Search Toggle --}}
            <div class="mb-4">
                <button type="button" @click="showAdvanced = !showAdvanced" 
                        class="text-sm font-medium hover:opacity-80 flex items-center gap-2" 
                        style="color: var(--brand-secondary-color);">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-90': showAdvanced }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span x-text="showAdvanced ? 'Hide Advanced Search' : 'Show Advanced Search'"></span>
                </button>
            </div>

            {{-- Advanced Search Fields --}}
            <div x-show="showAdvanced" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 style="display: none;">
                
                {{-- Transaction Status --}}
                <div class="border-t pt-4 mb-6" style="border-color: var(--sidebar-hover-background-color);">
                    <label class="block text-sm font-medium mb-3">Transaction Status</label>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="status[]" value="received" 
                                   {{ in_array('received', request('status', ['received', 'exchanged', 'settled'])) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded" style="accent-color: var(--status-success-color);">
                            <span class="text-sm">Received</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="status[]" value="exchanged" 
                                   {{ in_array('exchanged', request('status', ['received', 'exchanged', 'settled'])) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded" style="accent-color: var(--status-success-color);">
                            <span class="text-sm">Exchanged</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="status[]" value="settled" 
                                   {{ in_array('settled', request('status', ['received', 'exchanged', 'settled'])) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded" style="accent-color: var(--status-success-color);">
                            <span class="text-sm">Settled</span>
                        </label>
                    </div>
                </div>

                {{-- PHASE 2: EXCHANGED --}}
                <div class="mb-6">
                    <h3 class="text-sm font-semibold mb-3 pb-2 border-b" style="color: var(--status-success-color); border-color: var(--sidebar-hover-background-color);">PHASE # 2: EXCHANGED</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Exchange Amount</label>
                        <input type="number" step="0.00001" name="exchange_amount" value="{{ request('exchange_amount') }}" 
                               class="w-full px-3 py-2 rounded-md text-sm" 
                               style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Date Exchanged (From)</label>
                            <input type="date" name="date_exchanged_from" value="{{ request('date_exchanged_from') }}" 
                                   class="w-full px-3 py-2 rounded-md text-sm" 
                                   style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Date Exchanged (Through)</label>
                            <input type="date" name="date_exchanged_through" value="{{ request('date_exchanged_through') }}" 
                                   class="w-full px-3 py-2 rounded-md text-sm" 
                                   style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                    </div>
                </div>

                {{-- PHASE 3: SETTLEMENT --}}
                <div class="mb-6">
                    <h3 class="text-sm font-semibold mb-3 pb-2 border-b" style="color: var(--status-success-color); border-color: var(--sidebar-hover-background-color);">PHASE # 3: SETTLEMENT</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Settlement Amount</label>
                        <input type="number" step="0.00001" name="settlement_amount" value="{{ request('settlement_amount') }}" 
                               class="w-full px-3 py-2 rounded-md text-sm" 
                               style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Date Settled (From)</label>
                            <input type="date" name="date_settled_from" value="{{ request('date_settled_from') }}" 
                                   class="w-full px-3 py-2 rounded-md text-sm" 
                                   style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Date Settled (Through)</label>
                            <input type="date" name="date_settled_through" value="{{ request('date_settled_through') }}" 
                                   class="w-full px-3 py-2 rounded-md text-sm" 
                                   style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                    </div>
                </div>

                {{-- Date Updated --}}
                <div class="border-t pt-4" style="border-color: var(--sidebar-hover-background-color);">
                    <label class="block text-sm font-medium mb-3">Date Updated Range</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2 opacity-60">From</label>
                            <input type="date" name="date_updated_from" value="{{ request('date_updated_from') }}" 
                                   class="w-full px-3 py-2 rounded-md text-sm" 
                                   style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2 opacity-60">Through</label>
                            <input type="date" name="date_updated_through" value="{{ request('date_updated_through') }}" 
                                   class="w-full px-3 py-2 rounded-md text-sm" 
                                   style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Actions --}}
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 rounded-md font-semibold hover:opacity-80" 
                        style="background-color: var(--brand-secondary-color); color: white;">
                    Search
                </button>
                <a href="{{ route('modules.transactions') }}" class="px-6 py-2 rounded-md font-medium hover:opacity-80" 
                   style="background-color: var(--sidebar-hover-background-color);">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    {{-- Transaction Results --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        @if($transactions->count() > 0)
        <table class="w-full">
            <thead>
                <tr class="text-left text-sm border-b" style="border-color: var(--sidebar-hover-background-color); color: var(--status-warning-color);">
                    <th class="pb-3" style="width: 50px;"></th>
                    <th class="pb-3" style="width: 18%;">Date (UTC)</th>
                    <th class="pb-3 text-right" style="width: 27%;">Incoming</th>
                    <th class="pb-3 text-right" style="width: 27%;">Exchange</th>
                    <th class="pb-3 text-right" style="width: 28%;">Settlement</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                @php
                    $rowClass = $loop->odd ? 'background-color: rgba(255, 255, 255, 0.02);' : '';
                @endphp
                <tbody x-data="{ expanded: false, copySuccess: {} }">
                <tr style="{{ $rowClass }}">
                    <td class="py-2 px-2 cursor-pointer" @click="expanded = !expanded">
                        <svg class="w-4 h-4 transition-transform duration-200" :style="expanded ? 'transform: rotate(90deg)' : 'transform: rotate(0deg)'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </td>
                    <td class="py-2 px-2 text-sm cursor-pointer" @click="expanded = !expanded">
                        @if($transaction->datetime_created)
                            <div>{{ $transaction->datetime_created->format('M j, Y') }}</div>
                            <div class="text-xs opacity-60">{{ $transaction->datetime_created->format('H:i') }}</div>
                        @else
                            -
                        @endif
                    </td>
                    <td class="py-2 px-2 text-right cursor-pointer" @click="expanded = !expanded">
                        <small class="text-xs opacity-60">{{ $transaction->currency_code }}</small><br>
                        <span class="font-medium">{{ number_format($transaction->amount, 5) }}</span>
                    </td>
                    <td class="py-2 px-2 text-right cursor-pointer" @click="expanded = !expanded">
                        @if($transaction->datetime_exchanged && $transaction->settlement_amount)
                            <small class="text-xs opacity-60">{{ $transaction->settlement_currency_code ?? '-' }}</small><br>
                            <span class="font-medium">{{ number_format($transaction->settlement_amount, 5) }}</span>
                        @else
                            <small class="opacity-40">(PENDING)</small>
                        @endif
                    </td>
                    <td class="py-2 px-2 text-right cursor-pointer" @click="expanded = !expanded">
                        @if($transaction->datetime_settled && $transaction->final_settlement_amount)
                            <small class="text-xs opacity-60">{{ $transaction->final_settlement_currency_code ?? '-' }}</small><br>
                            <span class="font-medium">{{ number_format($transaction->final_settlement_amount, 5) }}</span>
                        @else
                            <small class="opacity-40">(PENDING)</small>
                        @endif
                    </td>
                </tr>
                <tr x-show="expanded" style="display: none; {{ $rowClass }}">
                    <td colspan="5" class="p-0">
                        <div class="px-8 py-4">
                            {{-- UPDATE Button --}}
                            <div class="flex justify-end mb-4">
                                <a href="{{ url('/administrator/accounting') }}?transaction_hash={{ $transaction->record_unique_identifier }}" 
                                   class="px-4 py-2 rounded text-sm font-semibold transition-opacity hover:opacity-80"
                                   style="background-color: #ef4444; color: white;">
                                    UPDATE
                                </a>
                            </div>

                            @php
                                $phase1Fields = [
                                    ['label' => 'Record Unique Identifier', 'value' => $transaction->record_unique_identifier],
                                    ['label' => 'Currency Code', 'value' => $transaction->currency_code],
                                    ['label' => 'Amount', 'value' => $transaction->amount],
                                    ['label' => 'Incoming Fixed Fee', 'value' => $transaction->incoming_fixed_fee],
                                    ['label' => 'Incoming Percentage Fee', 'value' => $transaction->incoming_percentage_fee],
                                    ['label' => 'Incoming Minimum Fee', 'value' => $transaction->incoming_minimum_fee],
                                    ['label' => 'Incoming Total Fee', 'value' => $transaction->incoming_total_fee],
                                    ['label' => 'Datetime Received', 'value' => $transaction->datetime_received ? $transaction->datetime_received->format('M j, Y H:i') : null],
                                    ['label' => 'Datetime Created', 'value' => $transaction->datetime_created ? $transaction->datetime_created->format('M j, Y H:i') : null],
                                    ['label' => 'Datetime Updated', 'value' => $transaction->datetime_updated ? $transaction->datetime_updated->format('M j, Y H:i') : null],
                                ];
                                
                                $isPhase2Pending = !$transaction->datetime_exchanged;
                                $phase2Fields = [
                                    ['label' => 'Exchange Ratio', 'value' => $isPhase2Pending ? null : $transaction->exchange_ratio],
                                    ['label' => 'Settlement Currency Code', 'value' => $isPhase2Pending ? null : $transaction->settlement_currency_code],
                                    ['label' => 'Settlement Amount', 'value' => $isPhase2Pending ? null : $transaction->settlement_amount],
                                    ['label' => 'Exchange Fixed Fee', 'value' => $isPhase2Pending ? null : $transaction->exchange_fixed_fee],
                                    ['label' => 'Exchange Percentage Fee', 'value' => $isPhase2Pending ? null : $transaction->exchange_percentage_fee],
                                    ['label' => 'Exchange Minimum Fee', 'value' => $isPhase2Pending ? null : $transaction->exchange_minimum_fee],
                                    ['label' => 'Exchange Total Fee', 'value' => $isPhase2Pending ? null : $transaction->exchange_total_fee],
                                    ['label' => 'Datetime Exchanged', 'value' => $transaction->datetime_exchanged ? $transaction->datetime_exchanged->format('M j, Y H:i') : null],
                                ];
                                
                                $isPhase3Pending = !$transaction->datetime_settled;
                                $phase3Fields = [
                                    ['label' => 'Settlement Account Type', 'value' => $isPhase3Pending ? null : $transaction->settlement_account_type],
                                    ['label' => 'Crypto Wallet Address', 'value' => $isPhase3Pending ? null : $transaction->crypto_wallet_address],
                                    ['label' => 'Crypto Network', 'value' => $isPhase3Pending ? null : $transaction->crypto_network],
                                    ['label' => 'Fiat Payment Method', 'value' => $isPhase3Pending ? null : $transaction->fiat_payment_method],
                                    ['label' => 'Fiat Bank Account Number', 'value' => $isPhase3Pending ? null : $transaction->fiat_bank_account_number],
                                    ['label' => 'Fiat Bank Routing Number', 'value' => $isPhase3Pending ? null : $transaction->fiat_bank_routing_number],
                                    ['label' => 'Fiat Bank SWIFT Code', 'value' => $isPhase3Pending ? null : $transaction->fiat_bank_swift_code],
                                    ['label' => 'Fiat Account Holder Name', 'value' => $isPhase3Pending ? null : $transaction->fiat_account_holder_name],
                                    ['label' => 'Fiat Bank Address', 'value' => $isPhase3Pending ? null : $transaction->fiat_bank_address],
                                    ['label' => 'Fiat Bank Country', 'value' => $isPhase3Pending ? null : $transaction->fiat_bank_country],
                                    ['label' => 'Outgoing Fixed Fee', 'value' => $isPhase3Pending ? null : $transaction->outgoing_fixed_fee],
                                    ['label' => 'Outgoing Percentage Fee', 'value' => $isPhase3Pending ? null : $transaction->outgoing_percentage_fee],
                                    ['label' => 'Outgoing Minimum Fee', 'value' => $isPhase3Pending ? null : $transaction->outgoing_minimum_fee],
                                    ['label' => 'Outgoing Total Fee', 'value' => $isPhase3Pending ? null : $transaction->outgoing_total_fee],
                                    ['label' => 'Final Settlement Currency Code', 'value' => $isPhase3Pending ? null : $transaction->final_settlement_currency_code],
                                    ['label' => 'Final Settlement Amount', 'value' => $isPhase3Pending ? null : $transaction->final_settlement_amount],
                                    ['label' => 'Datetime Settled', 'value' => $transaction->datetime_settled ? $transaction->datetime_settled->format('M j, Y H:i') : null],
                                ];
                            @endphp

                            {{-- PHASE 1: RECEIVED --}}
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold mb-3 pb-2 border-b" style="color: var(--status-success-color); border-color: var(--sidebar-hover-background-color);">PHASE # 1: RECEIVED</h3>
                                <div class="rounded-lg p-4 space-y-1 text-sm" style="background-color: rgba(255, 255, 255, 0.03);">
                                    @foreach($phase1Fields as $field)
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

                            {{-- PHASE 2: EXCHANGED --}}
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold mb-3 pb-2 border-b" style="color: var(--status-success-color); border-color: var(--sidebar-hover-background-color);">
                                    PHASE # 2: EXCHANGED @if($isPhase2Pending)<span style="color: var(--status-warning-color);">(PENDING)</span>@endif
                                </h3>
                                <div class="rounded-lg p-4 space-y-1 text-sm" style="background-color: rgba(255, 255, 255, 0.03);">
                                    @foreach($phase2Fields as $field)
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

                            {{-- PHASE 3: SETTLEMENT --}}
                            <div>
                                <h3 class="text-sm font-semibold mb-3 pb-2 border-b" style="color: var(--status-success-color); border-color: var(--sidebar-hover-background-color);">
                                    PHASE # 3: SETTLEMENT @if($isPhase3Pending)<span style="color: var(--status-warning-color);">(PENDING)</span>@endif
                                </h3>
                                <div class="rounded-lg p-4 space-y-1 text-sm" style="background-color: rgba(255, 255, 255, 0.03);">
                                    @foreach($phase3Fields as $field)
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
                        </div>
                    </td>
                </tr>
                </tbody>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <p class="text-sm opacity-60">No transactions yet.</p>
            <p class="text-xs opacity-40 mt-2">Your transaction history will appear here.</p>
        </div>
        @endif
    </div>
</div>
@endsection
