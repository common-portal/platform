@extends('layouts.platform')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>

<div class="max-w-4xl mx-auto" x-data="{
    expandedRows: {},
    rowDetails: {},
    showToast: false,
    toastMessage: '',
    toastType: 'success',

    toggleRow(hash) {
        this.expandedRows[hash] = !this.expandedRows[hash];
        if (this.expandedRows[hash] && !this.rowDetails[hash]) {
            this.loadTxDetail(hash);
        }
    },

    async loadTxDetail(hash) {
        try {
            const response = await fetch(`{{ url('/modules/wallets/tx') }}/${hash}/detail`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (data.success && data.detail) {
                this.rowDetails[hash] = data.detail;
            } else {
                this.rowDetails[hash] = { message: data.message || 'No tracking detail available.' };
            }
        } catch (e) {
            this.rowDetails[hash] = { message: 'Error loading tracking detail.' };
        }
    },

    copyValue(value) {
        navigator.clipboard.writeText(value);
        this.toastMessage = value;
        this.toastType = 'success';
        this.showToast = true;
        setTimeout(() => this.showToast = false, 3000);
    }
}">
    {{-- Toast Notification --}}
    <div x-cloak x-show="showToast"
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

    {{-- Header with back link --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('modules.transactions') }}" class="opacity-70 hover:opacity-100 transition-opacity">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold">{{ __translator('Crypto Exchange Transactions') }}</h1>
    </div>

    {{-- Filter Card --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: #333333;">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">{{ __translator('Filter Transactions') }}</h2>
        <form method="GET" action="{{ route('modules.transactions.crypto-exchange') }}">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Wallet') }}</label>
                    <select name="wallet_hash" class="w-full px-3 py-2 rounded-md text-sm"
                            style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        <option value="">{{ __translator('All Wallets') }}</option>
                        @foreach($wallets as $wallet)
                        <option value="{{ $wallet->record_unique_identifier }}" {{ request('wallet_hash') === $wallet->record_unique_identifier ? 'selected' : '' }}>
                            {{ $wallet->wallet_friendly_name }} ({{ $wallet->wallet_currency }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Direction') }}</label>
                    <select name="direction" class="w-full px-3 py-2 rounded-md text-sm"
                            style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        <option value="">{{ __translator('All') }}</option>
                        <option value="incoming" {{ request('direction') === 'incoming' ? 'selected' : '' }}>{{ __translator('Incoming') }}</option>
                        <option value="outgoing" {{ request('direction') === 'outgoing' ? 'selected' : '' }}>{{ __translator('Outgoing') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Status') }}</label>
                    <select name="status" class="w-full px-3 py-2 rounded-md text-sm"
                            style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        <option value="">{{ __translator('All') }}</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>{{ __translator('Submitted') }}</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>{{ __translator('Confirmed') }}</option>
                        <option value="finalized" {{ request('status') === 'finalized' ? 'selected' : '' }}>{{ __translator('Finalized') }}</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __translator('Failed') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Currency') }}</label>
                    <select name="currency" class="w-full px-3 py-2 rounded-md text-sm"
                            style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        <option value="">{{ __translator('All') }}</option>
                        <option value="USDT" {{ request('currency') === 'USDT' ? 'selected' : '' }}>USDT</option>
                        <option value="USDC" {{ request('currency') === 'USDC' ? 'selected' : '' }}>USDC</option>
                        <option value="EURC" {{ request('currency') === 'EURC' ? 'selected' : '' }}>EURC</option>
                        <option value="SOL" {{ request('currency') === 'SOL' ? 'selected' : '' }}>SOL</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 rounded-md font-medium"
                        style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                    {{ __translator('Search') }}
                </button>
                <a href="{{ route('modules.transactions.crypto-exchange') }}" class="px-6 py-2 rounded-md font-medium hover:opacity-80"
                   style="background-color: var(--content-background-color);">
                    {{ __translator('Reset') }}
                </a>
            </div>
        </form>
    </div>

    {{-- Transactions Table --}}
    <div class="rounded-lg overflow-hidden" style="background-color: var(--card-background-color);">
        <div class="px-6 py-4 border-b flex items-center justify-between" style="border-color: var(--sidebar-hover-background-color); background-color: var(--content-background-color);">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6" style="color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <h2 class="text-xl font-bold">{{ __translator('Crypto Exchange Transactions') }}</h2>
            </div>
            <span class="text-sm opacity-70">{{ $transactions->count() }} {{ __translator('transactions') }}</span>
        </div>

        @if($transactions->isEmpty())
        <div class="p-8 text-center opacity-70">
            <p>{{ __translator('No crypto exchange transactions found.') }}</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-sm border-b" style="border-color: var(--sidebar-hover-background-color); color: var(--status-warning-color);">
                        <th class="px-6 py-3" style="width: 30px;"></th>
                        <th class="px-4 py-3">{{ __translator('Date') }}</th>
                        <th class="px-4 py-3">{{ __translator('Wallet') }}</th>
                        <th class="px-4 py-3">{{ __translator('Direction') }}</th>
                        <th class="px-4 py-3 text-right">{{ __translator('Amount') }}</th>
                        <th class="px-4 py-3">{{ __translator('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                    <tr class="border-b cursor-pointer hover:opacity-80 transition-opacity"
                        style="border-color: rgba(255,255,255,0.05);"
                        @click="toggleRow('{{ $tx->record_unique_identifier }}')">
                        <td class="px-6 py-3">
                            <svg class="w-4 h-4 transition-transform"
                                 :class="expandedRows['{{ $tx->record_unique_identifier }}'] ? 'rotate-90' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ $tx->datetime_created ? $tx->datetime_created->format('M d, Y H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ $tx->wallet ? $tx->wallet->wallet_friendly_name : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($tx->direction === 'incoming')
                            <span class="flex items-center gap-1 text-sm" style="color: #22c55e;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                                {{ __translator('INCOMING') }}
                            </span>
                            @else
                            <span class="flex items-center gap-1 text-sm" style="color: #f59e0b;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                {{ __translator('OUTGOING') }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-sm">
                            {{ number_format($tx->amount, 2) }} {{ $tx->currency }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = [
                                    'submitted' => 'background-color: rgba(59,130,246,0.2); color: #3b82f6;',
                                    'confirmed' => 'background-color: rgba(34,197,94,0.2); color: #22c55e;',
                                    'finalized' => 'background-color: rgba(34,197,94,0.3); color: #16a34a;',
                                    'failed' => 'background-color: rgba(239,68,68,0.2); color: #ef4444;',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-medium"
                                  style="{{ $statusColors[$tx->transaction_status] ?? '' }}">
                                {{ ucfirst($tx->transaction_status) }}
                            </span>
                        </td>
                    </tr>

                    {{-- Expandable Detail Row --}}
                    <tr x-show="expandedRows['{{ $tx->record_unique_identifier }}']" x-cloak
                        style="background-color: var(--content-background-color);">
                        <td colspan="6" class="px-6 py-4">
                            <div class="space-y-3">
                                {{-- Basic Info --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="opacity-50">{{ __translator('From') }}:</span>
                                        <code class="ml-2 font-mono text-xs cursor-pointer hover:opacity-80" @click.stop="copyValue('{{ $tx->from_wallet_address }}')">{{ $tx->from_wallet_address }}</code>
                                    </div>
                                    <div>
                                        <span class="opacity-50">{{ __translator('To') }}:</span>
                                        <code class="ml-2 font-mono text-xs cursor-pointer hover:opacity-80" @click.stop="copyValue('{{ $tx->to_wallet_address }}')">{{ $tx->to_wallet_address }}</code>
                                    </div>
                                    @if($tx->memo_note)
                                    <div class="md:col-span-2">
                                        <span class="opacity-50">{{ __translator('Memo') }}:</span>
                                        <span class="ml-2">{{ $tx->memo_note }}</span>
                                    </div>
                                    @endif
                                </div>

                                {{-- Solana Tracking Detail --}}
                                @if($tx->solana_tx_signature)
                                <div class="mt-3 p-4 rounded-lg" style="background-color: var(--card-background-color);">
                                    <h4 class="font-semibold text-sm mb-3" style="color: var(--brand-primary-color);">{{ __translator('Solana Tracking Detail') }}</h4>

                                    <template x-if="rowDetails['{{ $tx->record_unique_identifier }}'] && !rowDetails['{{ $tx->record_unique_identifier }}'].message">
                                        <div class="space-y-3 text-sm">
                                            <div class="flex items-start gap-3">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background-color: var(--brand-primary-color); color: #1a1a2e;">1</div>
                                                <div>
                                                    <p class="font-medium">{{ __translator('Transaction Submitted') }}</p>
                                                    <p class="opacity-70 text-xs" x-text="'Tx: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.tx_signature || '')"></p>
                                                    <p class="opacity-70 text-xs" x-text="'Time: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.block_time || '')"></p>
                                                </div>
                                            </div>

                                            <div class="flex items-start gap-3">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background-color: var(--brand-primary-color); color: #1a1a2e;">2</div>
                                                <div>
                                                    <p class="font-medium">{{ __translator('Transaction Confirmed') }}</p>
                                                    <p class="opacity-70 text-xs" x-text="'Block Slot: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.block_slot || '')"></p>
                                                    <p class="opacity-70 text-xs" x-text="'Status: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.confirmation_status || '')"></p>
                                                    <p class="opacity-70 text-xs" x-text="'Fee: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.fee_sol || 0) + ' SOL'"></p>
                                                </div>
                                            </div>

                                            <div class="flex items-start gap-3">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background-color: var(--brand-primary-color); color: #1a1a2e;">3</div>
                                                <div>
                                                    <p class="font-medium">{{ __translator('Delivery Verified') }}</p>
                                                    <p class="opacity-70 text-xs" x-text="'Amount: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.token_amount || '') + ' ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.token_symbol || '')"></p>
                                                </div>
                                            </div>

                                            <div class="flex gap-3 mt-2">
                                                <a :href="rowDetails['{{ $tx->record_unique_identifier }}']?.explorer_url" target="_blank" class="text-xs underline hover:opacity-80" style="color: var(--brand-primary-color);">{{ __translator('View on Solana Explorer') }}</a>
                                                <a :href="rowDetails['{{ $tx->record_unique_identifier }}']?.solscan_url" target="_blank" class="text-xs underline hover:opacity-80" style="color: var(--brand-primary-color);">{{ __translator('View on Solscan') }}</a>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="rowDetails['{{ $tx->record_unique_identifier }}']?.message">
                                        <p class="text-sm opacity-70" x-text="rowDetails['{{ $tx->record_unique_identifier }}'].message"></p>
                                    </template>

                                    <template x-if="!rowDetails['{{ $tx->record_unique_identifier }}']">
                                        <p class="text-sm opacity-70">{{ __translator('Loading tracking detail...') }}</p>
                                    </template>
                                </div>
                                @else
                                <div class="mt-3 p-4 rounded-lg text-sm opacity-70" style="background-color: var(--card-background-color);">
                                    <p>{{ __translator('Solana transaction signature not yet available. Transaction may still be processing.') }}</p>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
