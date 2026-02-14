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
                        'EURC' => 'background-color: #7c3aed; color: white;',
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

    @endif
</div>
@endsection
