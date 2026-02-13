@extends('layouts.platform')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>
<script>
function clientWalletData() {
    return {
        csrfToken: '{{ csrf_token() }}',
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        showDetailModal: false,
        loadingDetail: false,
        
        // Detail modal
        trackingDetail: null,
        detailTxHash: '',
        
        // Expandable rows
        expandedRows: {},
        rowDetails: {},
        
        truncateAddress(addr) {
            if (!addr || addr.length < 12) return addr;
            return addr.substring(0, 6) + '...' + addr.substring(addr.length - 4);
        },
        
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.showNotification('COPIED:<br>' + text, 'success');
            });
        },
        
        async toggleRow(txHash) {
            if (this.expandedRows[txHash]) {
                this.expandedRows[txHash] = false;
                return;
            }
            
            this.expandedRows[txHash] = true;
            
            // Load detail if not already loaded
            if (!this.rowDetails[txHash]) {
                try {
                    const response = await fetch(`/modules/wallets/tx/${txHash}/detail`, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    });
                    
                    const data = await response.json();
                    if (data.success && data.detail) {
                        this.rowDetails[txHash] = data.detail;
                    } else {
                        this.rowDetails[txHash] = { message: data.message || 'No tracking detail available yet.' };
                    }
                } catch (error) {
                    console.error('Error loading detail:', error);
                    this.rowDetails[txHash] = { message: 'Error loading tracking detail.' };
                }
            }
        },
        
        showNotification(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.showToast = true;
            setTimeout(() => this.showToast = false, 4000);
        },
        
        formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + 
                   ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }
    };
}
</script>

<div class="max-w-7xl mx-auto" x-data="clientWalletData()">
    
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
         :style="toastType === 'success' ? 'background-color: #10b981; color: white;' : 'background-color: #ef4444; color: white;'">
        <div class="flex items-center gap-3">
            <svg x-show="toastType === 'success'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg x-show="toastType === 'error'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium" x-html="toastMessage"></span>
        </div>
    </div>

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-2">{{ __translator('Wallets') }}</h1>
        <p class="opacity-70">{{ __translator('View your crypto wallets and transaction history') }}</p>
    </div>

    @if($wallets->isEmpty())
    {{-- No Wallets Found --}}
    <div class="rounded-lg p-12 text-center" style="background-color: var(--card-background-color);">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <h3 class="text-lg font-semibold mb-2">{{ __translator('No Wallets Found') }}</h3>
        <p class="opacity-70">{{ __translator('No crypto wallets have been assigned to this account yet.') }}</p>
    </div>
    @else

    {{-- Wallet Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8"
         x-data="{ walletBalances: {}, loadingBalances: true }"
         x-init="
            fetch('/modules/wallets/balances', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => { if (data.success) walletBalances = data.balances; loadingBalances = false; })
                .catch(() => { loadingBalances = false; })
         ">
        @foreach($wallets as $wallet)
        <div class="rounded-lg p-6" style="background-color: var(--card-background-color); border: 1px solid var(--card-border-color);">
            {{-- Card Header --}}
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-base">{{ $wallet->wallet_friendly_name }}</h3>
                @php
                    $badgeStyle = match($wallet->wallet_currency) {
                        'USDT' => 'background-color: #26a17b; color: white;',
                        'EURC' => 'background-color: #0052ff; color: white;',
                        'SOL'  => 'background-color: #dc2626; color: white;',
                        default => 'background-color: #2775ca; color: white;',
                    };
                @endphp
                <span class="px-2 py-1 rounded text-xs font-medium" style="{{ $badgeStyle }}">
                    {{ $wallet->wallet_currency }}
                </span>
            </div>
            
            {{-- Balance --}}
            <div class="mb-4 p-4 rounded-md" style="background-color: var(--content-background-color);">
                <label class="block text-xs opacity-50 mb-1">Balance</label>
                <template x-if="loadingBalances">
                    <span class="text-sm opacity-50">Loading...</span>
                </template>
                <template x-if="!loadingBalances">
                    <span class="font-mono text-lg font-bold" x-text="Number(walletBalances['{{ $wallet->record_unique_identifier }}']?.balance || 0).toLocaleString('en-US', {minimumFractionDigits: 6, maximumFractionDigits: 6})"></span>
                </template>
            </div>
            
            {{-- Network --}}
            <div class="flex items-center gap-2 mb-4 text-sm opacity-70">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
                <span>Solana</span>
            </div>
            
            {{-- Wallet Address --}}
            <div class="mb-2">
                <label class="block text-xs opacity-50 mb-1">Wallet Address</label>
                <div class="flex items-center gap-2">
                    <code class="text-sm font-mono opacity-80 break-all">{{ $wallet->wallet_address }}</code>
                    <button @click="copyToClipboard('{{ $wallet->wallet_address }}')" class="opacity-50 hover:opacity-100" title="Copy full address">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="mt-5 mb-5 pt-5" style="border-top: 1px solid var(--card-border-color);"></div>

            {{-- Status --}}
            <div class="flex items-center">
                <span class="px-2 py-1 rounded text-xs"
                      style="{{ $wallet->is_active ? 'background-color: rgba(34,197,94,0.2); color: #22c55e;' : 'background-color: rgba(245,158,11,0.2); color: #f59e0b;' }}">
                    {{ $wallet->is_active ? 'Active' : 'Paused' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Transaction History --}}
    <div class="rounded-lg overflow-hidden" style="background-color: var(--card-background-color);">
        <div class="px-6 py-4 border-b" style="border-color: var(--sidebar-hover-background-color); background-color: var(--content-background-color);">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h2 class="text-xl font-bold">{{ __translator('Transaction History') }}</h2>
            </div>
        </div>

        @if($transactions->isEmpty())
        <div class="p-8 text-center opacity-70">
            <p>{{ __translator('No transactions yet.') }}</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-sm border-b" style="border-color: var(--sidebar-hover-background-color); color: var(--status-warning-color);">
                        <th class="px-6 py-3" style="width: 30px;"></th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Wallet</th>
                        <th class="px-4 py-3">Direction</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Status</th>
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
                                INCOMING
                            </span>
                            @else
                            <span class="flex items-center gap-1 text-sm" style="color: #f59e0b;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                OUTGOING
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
                                        <span class="opacity-50">From:</span>
                                        <code class="ml-2 font-mono text-xs">{{ $tx->from_wallet_address }}</code>
                                    </div>
                                    <div>
                                        <span class="opacity-50">To:</span>
                                        <code class="ml-2 font-mono text-xs">{{ $tx->to_wallet_address }}</code>
                                    </div>
                                    @if($tx->memo_note)
                                    <div class="md:col-span-2">
                                        <span class="opacity-50">Memo:</span>
                                        <span class="ml-2">{{ $tx->memo_note }}</span>
                                    </div>
                                    @endif
                                </div>
                                
                                {{-- Solana Tracking Detail --}}
                                @if($tx->solana_tx_signature)
                                <div class="mt-3 p-4 rounded-lg" style="background-color: var(--card-background-color);">
                                    <h4 class="font-semibold text-sm mb-3" style="color: var(--brand-primary-color);">Solana Tracking Detail</h4>
                                    
                                    <template x-if="rowDetails['{{ $tx->record_unique_identifier }}'] && !rowDetails['{{ $tx->record_unique_identifier }}'].message">
                                        <div class="space-y-3 text-sm">
                                            <div class="flex items-start gap-3">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background-color: var(--brand-primary-color); color: #1a1a2e;">1</div>
                                                <div>
                                                    <p class="font-medium">Transaction Submitted</p>
                                                    <p class="opacity-70 text-xs" x-text="'Tx: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.tx_signature || '')"></p>
                                                    <p class="opacity-70 text-xs" x-text="'Time: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.block_time || '')"></p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start gap-3">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background-color: var(--brand-primary-color); color: #1a1a2e;">2</div>
                                                <div>
                                                    <p class="font-medium">Transaction Confirmed</p>
                                                    <p class="opacity-70 text-xs" x-text="'Block Slot: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.block_slot || '')"></p>
                                                    <p class="opacity-70 text-xs" x-text="'Status: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.confirmation_status || '')"></p>
                                                    <p class="opacity-70 text-xs" x-text="'Fee: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.fee_sol || 0) + ' SOL'"></p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start gap-3">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background-color: var(--brand-primary-color); color: #1a1a2e;">3</div>
                                                <div>
                                                    <p class="font-medium">Delivery Verified</p>
                                                    <p class="opacity-70 text-xs" x-text="'Amount: ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.token_amount || '') + ' ' + (rowDetails['{{ $tx->record_unique_identifier }}']?.token_symbol || '')"></p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex gap-3 mt-2">
                                                <a :href="rowDetails['{{ $tx->record_unique_identifier }}']?.explorer_url" target="_blank" class="text-xs underline hover:opacity-80" style="color: var(--brand-primary-color);">View on Solana Explorer</a>
                                                <a :href="rowDetails['{{ $tx->record_unique_identifier }}']?.solscan_url" target="_blank" class="text-xs underline hover:opacity-80" style="color: var(--brand-primary-color);">View on Solscan</a>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <template x-if="rowDetails['{{ $tx->record_unique_identifier }}']?.message">
                                        <p class="text-sm opacity-70" x-text="rowDetails['{{ $tx->record_unique_identifier }}'].message"></p>
                                    </template>
                                    
                                    <template x-if="!rowDetails['{{ $tx->record_unique_identifier }}']">
                                        <p class="text-sm opacity-70">Loading tracking detail...</p>
                                    </template>
                                </div>
                                @else
                                <div class="mt-3 p-4 rounded-lg text-sm opacity-70" style="background-color: var(--card-background-color);">
                                    <p>Solana transaction signature not yet available. Transaction may still be processing.</p>
                                    @if($tx->solana_tx_signature)
                                    <p class="mt-1 font-mono text-xs">Tx: {{ $tx->solana_tx_signature }}</p>
                                    @endif
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
    @endif
</div>
@endsection
