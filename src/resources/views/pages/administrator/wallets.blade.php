@extends('layouts.platform')

@section('content')
<style>[x-cloak] { display: none !important; }</style>
<script>
function walletData() {
    return {
        csrfToken: '{{ csrf_token() }}',
        accountHash: '',
        wallets: [],
        selectedWalletHash: '',
        isNewWallet: true,
        loading: false,
        saving: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        
        // Form fields
        friendlyName: '',
        walletCurrency: 'USDT',
        walletNetwork: 'solana',
        walletType: 'client',
        isActive: true,
        
        // Display fields (read-only after creation)
        walletAddress: '',
        walletidsHash: '',
        
        // Balance fields
        solBalance: null,
        solLow: false,
        tokenBalance: null,
        loadingBalances: false,
        
        async init() {
            // Check if there's an impersonated account
            @if(session('admin_impersonating_from') && session('active_account_id'))
                @php
                    $impersonatedAccount = \App\Models\TenantAccount::find(session('active_account_id'));
                @endphp
                @if($impersonatedAccount)
                    this.accountHash = '{{ $impersonatedAccount->record_unique_identifier }}';
                    await this.loadWallets();
                @endif
            @endif
        },
        
        async loadWallets() {
            if (!this.accountHash) return;
            
            this.loading = true;
            try {
                let url = `{{ route("admin.wallets.list") }}?account_hash=${this.accountHash}`;
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.wallets = data.wallets;
                }
            } catch (error) {
                console.error('Error loading wallets:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async onWalletSelect() {
            if (this.selectedWalletHash === '' || this.selectedWalletHash === 'new') {
                this.isNewWallet = true;
                this.resetForm();
                return;
            }
            
            this.isNewWallet = false;
            this.loading = true;
            
            try {
                const response = await fetch(`{{ url('/administrator/wallets') }}/${this.selectedWalletHash}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.friendlyName = data.wallet.wallet_friendly_name;
                    this.walletCurrency = data.wallet.wallet_currency;
                    this.walletNetwork = data.wallet.wallet_network;
                    this.walletType = data.wallet.wallet_type || 'dynamic';
                    this.walletAddress = data.wallet.wallet_address;
                    this.walletidsHash = data.wallet.walletids_wallet_hash;
                    this.isActive = data.wallet.is_active;
                    
                    // Set balances from API response
                    if (data.balances) {
                        this.solBalance = data.balances.sol_balance;
                        this.solLow = data.balances.sol_low;
                        this.tokenBalance = data.balances.token_ui_amount;
                    }
                }
            } catch (error) {
                console.error('Error loading wallet:', error);
                this.showNotification('Error loading wallet details', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        resetForm() {
            this.friendlyName = '';
            this.walletCurrency = 'USDT';
            this.walletNetwork = 'solana';
            this.walletType = 'client';
            this.walletAddress = '';
            this.walletidsHash = '';
            this.isActive = true;
            this.solBalance = null;
            this.solLow = false;
            this.tokenBalance = null;
        },
        
        async saveWallet() {
            if (!this.accountHash) {
                this.showNotification('Please select an account first', 'error');
                return;
            }
            
            if (!this.friendlyName) {
                this.showNotification('Please enter a wallet name', 'error');
                return;
            }
            
            this.saving = true;
            
            try {
                let url, method;
                let formData;
                
                if (this.isNewWallet) {
                    url = '{{ route("admin.wallets.store") }}';
                    method = 'POST';
                    formData = {
                        wallet_friendly_name: this.friendlyName,
                        wallet_currency: this.walletCurrency,
                        wallet_network: this.walletNetwork,
                        wallet_type: this.walletType,
                        account_hash: this.accountHash,
                    };
                } else {
                    url = `{{ url('/administrator/wallets') }}/${this.selectedWalletHash}`;
                    method = 'PUT';
                    formData = {
                        wallet_friendly_name: this.friendlyName,
                        account_hash: this.accountHash,
                        is_active: this.isActive,
                    };
                }
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification(data.message, 'success');
                    await this.loadWallets();
                    
                    if (this.isNewWallet && data.wallet) {
                        this.selectedWalletHash = data.wallet.record_unique_identifier;
                        this.walletAddress = data.wallet.wallet_address;
                        this.walletidsHash = data.wallet.walletids_wallet_hash;
                        this.isNewWallet = false;
                    }
                } else {
                    this.showNotification(data.message || 'Error saving wallet', 'error');
                }
            } catch (error) {
                console.error('Error saving wallet:', error);
                this.showNotification('Network error while saving', 'error');
            } finally {
                this.saving = false;
            }
        },
        
        async deleteWallet() {
            if (this.isNewWallet || !this.selectedWalletHash) return;
            
            if (!confirm('Are you sure you want to delete this wallet? This action cannot be undone.')) return;
            
            this.saving = true;
            
            try {
                const response = await fetch(`{{ url('/administrator/wallets') }}/${this.selectedWalletHash}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Wallet deleted successfully', 'success');
                    this.selectedWalletHash = '';
                    this.isNewWallet = true;
                    this.resetForm();
                    await this.loadWallets();
                } else {
                    this.showNotification(data.message || 'Error deleting wallet', 'error');
                }
            } catch (error) {
                console.error('Error deleting wallet:', error);
                this.showNotification('Network error while deleting', 'error');
            } finally {
                this.saving = false;
            }
        },
        
        showNotification(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.showToast = true;
            setTimeout(() => this.showToast = false, 4000);
        },
        
        onAccountChange(event) {
            const select = event.target;
            const selectedOption = select.options[select.selectedIndex];
            this.accountHash = selectedOption.dataset.hash || '';
            this.selectedWalletHash = '';
            this.isNewWallet = true;
            this.resetForm();
            this.wallets = [];
            if (this.accountHash) {
                this.loadWallets();
            }
        },
        
        truncateAddress(addr) {
            if (!addr || addr.length < 12) return addr;
            return addr.substring(0, 6) + '...' + addr.substring(addr.length - 4);
        },
        
        copyAddress(addr) {
            navigator.clipboard.writeText(addr).then(() => {
                this.showNotification('Address copied to clipboard', 'success');
            });
        }
    };
}
</script>

<div class="max-w-4xl mx-auto" x-data="walletData()">
    
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
         :style="toastType === 'success' ? 'background-color: var(--brand-primary-color); color: #1a1a2e;' : 'background-color: #ef4444; color: white;'">
        <div class="flex items-center gap-3">
            <svg x-show="toastType === 'success'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg x-show="toastType === 'error'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium" x-text="toastMessage"></span>
        </div>
    </div>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Crypto Wallets Management</h1>
        <a href="{{ route('admin.index') }}" class="px-4 py-2 rounded-md hover:opacity-80" style="background-color: var(--card-background-color);">
            ← Back to Admin
        </a>
    </div>

    {{-- Card 1: Account Selection --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Select Account</h2>
        
        @if(session('admin_impersonating_from') && session('active_account_id'))
            @php $impersonatedAccount = \App\Models\TenantAccount::find(session('active_account_id')); @endphp
            @if($impersonatedAccount)
            <div>
                <label class="block text-sm font-medium mb-2">Account *</label>
                <div class="flex items-center gap-3">
                    <input type="text" value="{{ $impersonatedAccount->account_display_name }} ({{ $impersonatedAccount->primary_contact_email_address }})" readonly class="flex-1 px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;">
                    <a href="{{ route('admin.accounts') }}" class="px-4 py-2 rounded-md font-medium whitespace-nowrap" style="background-color: #9333ea; color: #ffffff;">
                        Change Account
                    </a>
                </div>
                <p class="text-xs mt-2 opacity-70">Managing wallets for the impersonated account.</p>
            </div>
            @endif
        @else
        <div>
            <label class="block text-sm font-medium mb-2">Account *</label>
            <select @change="onAccountChange($event)" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                <option value="">Select Account</option>
                @foreach($accounts as $account)
                <option value="{{ $account->id }}" data-hash="{{ $account->record_unique_identifier }}">{{ $account->account_display_name }} ({{ $account->account_type }})</option>
                @endforeach
            </select>
            <p class="text-xs mt-2 opacity-70">Select an account to manage its wallets. For treasury wallets, create or select your xramp operations account.</p>
        </div>
        @endif
    </div>

    {{-- Card 2: Wallet Selector --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Select Wallet</h2>
        
        <div>
            <label class="block text-sm font-medium mb-2">Choose Wallet to Edit or Add New</label>
            <select x-model="selectedWalletHash" @change="onWalletSelect()" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                <option value="new">+ CREATE NEW WALLET</option>
                <template x-for="wallet in wallets" :key="wallet.record_unique_identifier">
                    <option :value="wallet.record_unique_identifier" x-text="`${wallet.wallet_friendly_name} (${wallet.wallet_currency} / ${wallet.wallet_network}) [${(wallet.wallet_type || 'client').toUpperCase()}] - ${truncateAddress(wallet.wallet_address)}`"></option>
                </template>
            </select>
            <p class="text-xs mt-2 opacity-70" x-show="wallets.length === 0 && !loading">No existing wallets found. Create a new one below.</p>
            <p class="text-xs mt-2 opacity-70" x-show="loading">Loading wallets...</p>
        </div>
    </div>

    {{-- Card 3: Wallet Form --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);" x-text="isNewWallet ? 'Create New Wallet' : 'Edit Wallet'"></h2>
        
        <div class="space-y-4">
            {{-- Friendly Name --}}
            <div>
                <label class="block text-sm font-medium mb-2">Wallet Friendly Name *</label>
                <input type="text" x-model="friendlyName" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="e.g., XRAMP Master USDT Wallet">
            </div>
            
            {{-- Wallet Type --}}
            <div x-show="isNewWallet">
                <label class="block text-sm font-medium mb-2">Wallet Type *</label>
                <div class="flex items-center gap-4">
                    <button type="button" 
                            @click="walletType = 'client'; if (walletCurrency === 'SOL') walletCurrency = 'USDT';" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :style="walletType === 'client' ? 'background-color: #f59e0b; color: #1a1a2e;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        CLIENT
                    </button>
                    <button type="button" 
                            @click="walletType = 'admin'; if (walletCurrency === 'SOL') walletCurrency = 'USDT';" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :style="walletType === 'admin' ? 'background-color: #8b5cf6; color: white;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        ADMIN
                    </button>
                    <button type="button" 
                            @click="walletType = 'gas'; walletCurrency = 'SOL';" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :style="walletType === 'gas' ? 'background-color: #ef4444; color: white;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        GAS
                    </button>
                </div>
                <p class="text-xs mt-1 opacity-70">&bull; CLIENT wallets settle all available funds to a single, pre-verified 3rd party wallet address.<br>&bull; ADMIN wallets may distribute a portion of the wallet's funds to any available internal wallet address.<br>&bull; GAS wallet holds SOL to pay transaction fees for all other wallets. Only one GAS wallet is allowed.</p>
            </div>

            {{-- Read-only wallet type for existing --}}
            <div x-show="!isNewWallet">
                <label class="block text-sm font-medium mb-2">Wallet Type</label>
                <input type="text" :value="walletType ? walletType.toUpperCase() : 'CLIENT'" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;">
            </div>

            {{-- Currency --}}
            <div x-show="isNewWallet">
                <label class="block text-sm font-medium mb-2">Currency *</label>
                <div class="flex items-center gap-4">
                    <button type="button" 
                            @click="if (walletType !== 'gas') walletCurrency = 'USDT'" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :disabled="walletType === 'gas'"
                            :style="walletCurrency === 'USDT' ? 'background-color: #26a17b; color: white;' : walletType === 'gas' ? 'background-color: var(--content-background-color); opacity: 0.3; cursor: not-allowed;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        USDT (Tether)
                    </button>
                    <button type="button" 
                            @click="if (walletType !== 'gas') walletCurrency = 'USDC'" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :disabled="walletType === 'gas'"
                            :style="walletCurrency === 'USDC' ? 'background-color: #2775ca; color: white;' : walletType === 'gas' ? 'background-color: var(--content-background-color); opacity: 0.3; cursor: not-allowed;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        USDC (Circle)
                    </button>
                    <button type="button" 
                            @click="if (walletType !== 'gas') walletCurrency = 'EURC'" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :disabled="walletType === 'gas'"
                            :style="walletCurrency === 'EURC' ? 'background-color: #7c3aed; color: white;' : walletType === 'gas' ? 'background-color: var(--content-background-color); opacity: 0.3; cursor: not-allowed;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        EURC (Circle)
                    </button>
                    <button type="button" 
                            @click="if (walletType === 'gas') walletCurrency = 'SOL'" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :disabled="walletType !== 'gas'"
                            :style="walletCurrency === 'SOL' ? 'background-color: #9945FF; color: white;' : walletType !== 'gas' ? 'background-color: var(--content-background-color); opacity: 0.3; cursor: not-allowed;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        SOL (Solana)
                    </button>
                </div>
                <p x-show="walletType === 'gas'" class="text-xs mt-1 opacity-70">GAS wallets hold SOL only. One GAS wallet covers fees for all USDT and USDC transfers on this network.</p>
            </div>
            
            {{-- Read-only currency for existing --}}
            <div x-show="!isNewWallet">
                <label class="block text-sm font-medium mb-2">Currency</label>
                <input type="text" :value="walletCurrency" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;">
            </div>
            
            {{-- Network --}}
            <div>
                <label class="block text-sm font-medium mb-2">Network</label>
                <input type="text" value="Solana" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;">
                <p class="text-xs mt-1 opacity-70">Solana is the preferred network for USDT/USDC/EURC transfers (~$0.001 fees, ~400ms confirmations).</p>
            </div>
            
            {{-- Wallet Address (read-only, shown after creation) --}}
            <div x-show="!isNewWallet && walletAddress">
                <label class="block text-sm font-medium mb-2">Wallet Address</label>
                <div class="flex items-center gap-2">
                    <input type="text" :value="walletAddress" readonly class="flex-1 px-3 py-2 rounded-md font-mono text-sm" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;">
                    <button @click="copyAddress(walletAddress)" class="px-3 py-2 rounded-md hover:opacity-80" style="background-color: var(--content-background-color);" title="Copy address">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-xs mt-1 opacity-70">Created via WalletIDs.net. This address is on the Solana blockchain.</p>
            </div>
            
            {{-- Balances (edit mode only, shown after load) --}}
            <div x-show="!isNewWallet && walletAddress" class="space-y-3">
                <label class="block text-sm font-medium mb-2">Balances</label>
                
                {{-- SOL Balance (gas fees) --}}
                <div class="flex items-center justify-between p-3 rounded-md" 
                     :style="solLow ? 'background-color: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3);' : 'background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);'">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">SOL (gas fees)</span>
                        <template x-if="solLow">
                            <span class="px-2 py-0.5 rounded text-xs font-bold" style="background-color: #ef4444; color: white;">LOW</span>
                        </template>
                    </div>
                    <div class="text-right">
                        <template x-if="solBalance !== null">
                            <span class="font-mono text-sm" x-text="solBalance + ' SOL'"></span>
                        </template>
                        <template x-if="solBalance === null">
                            <span class="text-sm opacity-50">—</span>
                        </template>
                    </div>
                </div>
                <p x-show="solLow" class="text-xs" style="color: #ef4444;">
                    ⚠ SOL balance is below 0.01 SOL. This wallet cannot pay transaction fees. Fund it with SOL before sending.
                </p>
                
                {{-- Token Balance (USDT/USDC) --}}
                <div class="flex items-center justify-between p-3 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    <span class="text-sm font-medium" x-text="walletCurrency + ' Balance'"></span>
                    <div class="text-right">
                        <template x-if="tokenBalance !== null">
                            <span class="font-mono text-sm font-bold" x-text="Number(tokenBalance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 6}) + ' ' + walletCurrency"></span>
                        </template>
                        <template x-if="tokenBalance === null">
                            <span class="text-sm opacity-50">No token account found</span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Status Toggle (edit mode only) --}}
            <div x-show="!isNewWallet">
                <label class="block text-sm font-medium mb-2">Wallet Status</label>
                <div class="flex items-center gap-4">
                    <button type="button" 
                            @click="isActive = true" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :style="isActive ? 'background-color: var(--brand-primary-color); color: #1a1a2e;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        ACTIVE
                    </button>
                    <button type="button" 
                            @click="isActive = false" 
                            class="px-4 py-2 rounded-md font-medium transition-all"
                            :style="!isActive ? 'background-color: #f59e0b; color: #1a1a2e;' : 'background-color: var(--content-background-color); opacity: 0.6;'">
                        PAUSED
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 4: Actions --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <button type="button" 
                        x-show="!isNewWallet"
                        @click="deleteWallet()" 
                        :disabled="saving"
                        class="px-4 py-2 rounded-md font-medium hover:opacity-80 flex items-center gap-2" 
                        style="background-color: #dc2626; color: white;">
                    <span>Soft Delete</span>
                </button>
                
            </div>
            
            <button type="button" 
                    @click="saveWallet()" 
                    :disabled="saving || !friendlyName"
                    class="px-6 py-2 rounded-md font-medium hover:opacity-80 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" 
                    style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                <svg x-show="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isNewWallet ? 'Create Wallet (via WalletIDs.net)' : 'Update Wallet'"></span>
            </button>
        </div>
    </div>

    {{-- Card 5: Existing Wallets List --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);" x-show="wallets.length > 0">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">All Wallets</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color: rgba(255,255,255,0.1);">
                        <th class="text-left py-3 px-2">Name</th>
                        <th class="text-left py-3 px-2">Type</th>
                        <th class="text-left py-3 px-2">Currency</th>
                        <th class="text-left py-3 px-2">Address</th>
                        <th class="text-left py-3 px-2">Status</th>
                        <th class="text-right py-3 px-2">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="wallet in wallets" :key="wallet.record_unique_identifier">
                        <tr class="border-b" style="border-color: rgba(255,255,255,0.05);">
                            <td class="py-3 px-2 font-medium" x-text="wallet.wallet_friendly_name"></td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-1 rounded text-xs font-medium"
                                      :style="wallet.wallet_type === 'client' ? 'background-color: rgba(245,158,11,0.2); color: #f59e0b;' : wallet.wallet_type === 'gas' ? 'background-color: rgba(239,68,68,0.2); color: #ef4444;' : 'background-color: rgba(139,92,246,0.2); color: #8b5cf6;'"
                                      x-text="wallet.wallet_type ? wallet.wallet_type.toUpperCase() : 'CLIENT'">
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-1 rounded text-xs font-medium"
                                      :style="wallet.wallet_currency === 'USDT' ? 'background-color: #26a17b; color: white;' : wallet.wallet_currency === 'EURC' ? 'background-color: #7c3aed; color: white;' : wallet.wallet_currency === 'SOL' ? 'background-color: #dc2626; color: white;' : 'background-color: #2775ca; color: white;'"
                                      x-text="wallet.wallet_currency">
                                </span>
                            </td>
                            <td class="py-3 px-2 font-mono text-xs">
                                <span class="cursor-pointer hover:opacity-80" @click="copyAddress(wallet.wallet_address)" :title="wallet.wallet_address" x-text="truncateAddress(wallet.wallet_address)"></span>
                            </td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-1 rounded text-xs"
                                      :style="wallet.is_active ? 'background-color: rgba(34,197,94,0.2); color: #22c55e;' : 'background-color: rgba(245,158,11,0.2); color: #f59e0b;'"
                                      x-text="wallet.is_active ? 'Active' : 'Paused'">
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right font-mono text-xs">
                                <span class="font-medium" x-text="(() => { let bal = wallet.wallet_type === 'gas' ? wallet.sol_balance : wallet.token_balance; return Number(bal || 0).toLocaleString('en-US', {minimumFractionDigits: 6, maximumFractionDigits: 6}); })()"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
