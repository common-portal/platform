@extends('layouts.platform')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>

<div class="max-w-2xl mx-auto" x-data="{
    selectedWalletHash: '',
    selectedWallet: null,
    walletBalance: null,
    loadingBalance: false,
    amount: '',
    destinationAddress: '',
    memo: '',
    submitting: false,
    showToast: false,
    toastMessage: '',
    toastType: 'success',

    wallets: @js($wallets->map(fn($w) => ['hash' => $w->record_unique_identifier, 'name' => $w->wallet_friendly_name, 'currency' => $w->wallet_currency, 'network' => $w->wallet_network, 'address' => $w->wallet_address])),

    selectWallet() {
        this.selectedWallet = this.wallets.find(w => w.hash === this.selectedWalletHash) || null;
        this.walletBalance = null;
        if (this.selectedWallet) {
            this.loadBalance();
        }
    },

    async loadBalance() {
        this.loadingBalance = true;
        try {
            const response = await fetch(`{{ url('/modules/wallets') }}/${this.selectedWalletHash}/balance`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (data.success) {
                this.walletBalance = data.balance;
            }
        } catch (e) {
            console.error('Failed to load balance', e);
        }
        this.loadingBalance = false;
    },

    setMax() {
        if (this.walletBalance !== null) {
            this.amount = this.walletBalance;
        }
    },

    async submitPayout() {
        if (!this.selectedWalletHash || !this.amount || !this.destinationAddress) return;
        this.submitting = true;

        try {
            const response = await fetch(`{{ url('/modules/wallets') }}/${this.selectedWalletHash}/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    amount: this.amount,
                    destination_address: this.destinationAddress,
                    memo: this.memo || null
                })
            });

            const data = await response.json();

            if (data.success) {
                this.toastType = 'success';
                this.toastMessage = data.message || 'Payout submitted successfully.';
                this.amount = '';
                this.destinationAddress = '';
                this.memo = '';
                this.loadBalance();
            } else {
                this.toastType = 'error';
                this.toastMessage = data.message || 'Payout failed. Please try again.';
            }
        } catch (e) {
            this.toastType = 'error';
            this.toastMessage = 'Network error. Please try again.';
        }

        this.showToast = true;
        setTimeout(() => this.showToast = false, 5000);
        this.submitting = false;
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
         :style="toastType === 'success' ? 'background-color: #10b981; color: white;' : 'background-color: #ef4444; color: white;'">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <template x-if="toastType === 'success'">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </template>
                <template x-if="toastType === 'error'">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </template>
            </svg>
            <span class="text-sm font-medium" x-text="toastMessage"></span>
        </div>
    </div>

    {{-- Header with back link --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('modules.payout') }}" class="opacity-70 hover:opacity-100 transition-opacity">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold">{{ __translator('Payout Crypto') }}</h1>
    </div>

    {{-- Payout Form --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">

        {{-- Step 1: Source Wallet --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2" style="color: var(--status-warning-color);">
                {{ __translator('1. Source Wallet') }}
            </label>
            <select x-model="selectedWalletHash" @change="selectWallet()"
                    class="w-full px-4 py-3 rounded-md text-sm"
                    style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                <option value="">{{ __translator('Select a wallet...') }}</option>
                @foreach($wallets as $wallet)
                <option value="{{ $wallet->record_unique_identifier }}">
                    {{ $wallet->wallet_friendly_name }} ({{ $wallet->wallet_currency }} — {{ ucfirst($wallet->wallet_network) }})
                </option>
                @endforeach
            </select>

            {{-- Balance Display --}}
            <div x-show="selectedWallet" x-cloak class="mt-3 p-3 rounded-md" style="background-color: var(--content-background-color);">
                <div class="flex items-center justify-between text-sm">
                    <span class="opacity-70">{{ __translator('Available Balance') }}:</span>
                    <div>
                        <template x-if="loadingBalance">
                            <span class="opacity-50">{{ __translator('Loading...') }}</span>
                        </template>
                        <template x-if="!loadingBalance && walletBalance !== null">
                            <span class="font-mono font-semibold" x-text="parseFloat(walletBalance).toFixed(6) + ' ' + (selectedWallet?.currency || '')"></span>
                        </template>
                        <template x-if="!loadingBalance && walletBalance === null">
                            <span class="opacity-50">{{ __translator('Unavailable') }}</span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Amount --}}
        <div class="mb-6" x-show="selectedWallet" x-cloak>
            <label class="block text-sm font-semibold mb-2" style="color: var(--status-warning-color);">
                {{ __translator('2. Amount') }}
            </label>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input type="number" step="0.000001" x-model="amount"
                           placeholder="0.00"
                           class="w-full px-4 py-3 rounded-md text-sm font-mono"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold opacity-50"
                          x-text="selectedWallet?.currency || ''"></span>
                </div>
                <button type="button" @click="setMax()"
                        class="px-4 py-3 rounded-md text-xs font-semibold"
                        style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);"
                        :disabled="walletBalance === null">
                    {{ __translator('MAX') }}
                </button>
            </div>
        </div>

        {{-- Step 3: Rail (auto-displayed) --}}
        <div class="mb-6" x-show="selectedWallet" x-cloak>
            <label class="block text-sm font-semibold mb-2" style="color: var(--status-warning-color);">
                {{ __translator('3. Network Rail') }}
            </label>
            <div class="px-4 py-3 rounded-md text-sm flex items-center gap-2"
                 style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                <svg class="w-4 h-4" style="color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="font-medium" x-text="(selectedWallet?.network || '').charAt(0).toUpperCase() + (selectedWallet?.network || '').slice(1)"></span>
                <span class="opacity-50 text-xs">{{ __translator('(determined by source wallet)') }}</span>
            </div>
        </div>

        {{-- Step 4: Destination --}}
        <div class="mb-6" x-show="selectedWallet" x-cloak>
            <label class="block text-sm font-semibold mb-2" style="color: var(--status-warning-color);">
                {{ __translator('4. Destination Wallet Address') }}
            </label>
            <input type="text" x-model="destinationAddress"
                   placeholder="{{ __translator('e.g., 7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU') }}"
                   class="w-full px-4 py-3 rounded-md text-sm font-mono"
                   style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">

            <div class="mt-3">
                <label class="block text-sm opacity-70 mb-1">{{ __translator('Memo (optional)') }}</label>
                <input type="text" x-model="memo"
                       placeholder="{{ __translator('Optional memo or reference') }}"
                       class="w-full px-4 py-3 rounded-md text-sm"
                       style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
            </div>
        </div>

        {{-- Submit --}}
        <div x-show="selectedWallet" x-cloak>
            <button @click="submitPayout()"
                    :disabled="submitting || !amount || !destinationAddress"
                    class="w-full px-6 py-3 rounded-md font-semibold text-sm transition-opacity"
                    :class="submitting ? 'opacity-50 cursor-wait' : 'hover:opacity-80'"
                    style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                <span x-show="!submitting">{{ __translator('Submit Payout') }}</span>
                <span x-show="submitting" x-cloak>{{ __translator('Processing...') }}</span>
            </button>
        </div>

        {{-- Empty State --}}
        @if($wallets->isEmpty())
        <div class="text-center py-8 opacity-70">
            <p>{{ __translator('No wallets found. Please create a wallet first.') }}</p>
            <a href="{{ route('modules.wallets') }}" class="mt-3 inline-block text-sm underline" style="color: var(--brand-primary-color);">
                {{ __translator('Go to Wallets') }}
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
