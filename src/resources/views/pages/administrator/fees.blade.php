@extends('layouts.platform')

@section('content')
<style>[x-cloak] { display: none !important; }</style>
<script>
function feeData() {
    return {
        csrfToken: '{{ csrf_token() }}',
        accountHash: '{{ $impersonatedAccount?->record_unique_identifier ?? '' }}',
        
        // GBP Fees
        gbpFixedFee: '0.00',
        gbpPercentageFee: '0.00',
        gbpMinimumFee: '0.00',
        gbpHash: null,
        
        // EUR Fees
        eurFixedFee: '0.00',
        eurPercentageFee: '0.00',
        eurMinimumFee: '0.00',
        eurHash: null,
        
        loading: false,
        saving: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        
        async init() {
            if (this.accountHash) {
                await this.loadFees();
            }
        },
        
        async loadFees() {
            if (!this.accountHash) return;
            
            this.loading = true;
            try {
                const response = await fetch(`{{ url('/administrator/fees/list') }}?account_hash=${this.accountHash}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    if (data.fees.GBP) {
                        this.gbpFixedFee = data.fees.GBP.fixed_fee;
                        this.gbpPercentageFee = data.fees.GBP.percentage_fee;
                        this.gbpMinimumFee = data.fees.GBP.minimum_fee;
                        this.gbpHash = data.fees.GBP.hash;
                    }
                    if (data.fees.EUR) {
                        this.eurFixedFee = data.fees.EUR.fixed_fee;
                        this.eurPercentageFee = data.fees.EUR.percentage_fee;
                        this.eurMinimumFee = data.fees.EUR.minimum_fee;
                        this.eurHash = data.fees.EUR.hash;
                    }
                }
            } catch (error) {
                console.error('Error loading fees:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async saveFees(currencyCode) {
            if (!this.accountHash) {
                this.showNotification('Please select an account first', 'error');
                return;
            }
            
            this.saving = true;
            
            const isGBP = currencyCode === 'GBP';
            const formData = {
                account_hash: this.accountHash,
                currency_code: currencyCode,
                fixed_fee: isGBP ? this.gbpFixedFee : this.eurFixedFee,
                percentage_fee: isGBP ? this.gbpPercentageFee : this.eurPercentageFee,
                minimum_fee: isGBP ? this.gbpMinimumFee : this.eurMinimumFee,
            };
            
            try {
                const response = await fetch('{{ route('admin.fees.store') }}', {
                    method: 'POST',
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
                    if (data.fee) {
                        if (currencyCode === 'GBP') {
                            this.gbpHash = data.fee.hash;
                        } else {
                            this.eurHash = data.fee.hash;
                        }
                    }
                } else {
                    this.showNotification(data.message || 'Error saving fees', 'error');
                }
            } catch (error) {
                console.error('Error saving fees:', error);
                this.showNotification('Network error while saving', 'error');
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
            this.resetFees();
            if (this.accountHash) {
                this.loadFees();
            }
        },
        
        resetFees() {
            this.gbpFixedFee = '0.00';
            this.gbpPercentageFee = '0.00';
            this.gbpMinimumFee = '0.00';
            this.gbpHash = null;
            this.eurFixedFee = '0.00';
            this.eurPercentageFee = '0.00';
            this.eurMinimumFee = '0.00';
            this.eurHash = null;
        }
    };
}
</script>

<div class="max-w-4xl mx-auto" x-data="feeData()">
    
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
        <h1 class="text-2xl font-bold">Account Fees Management</h1>
        <a href="{{ route('admin.index') }}" class="px-4 py-2 rounded-md hover:opacity-80" style="background-color: var(--card-background-color);">
            ← Back to Admin
        </a>
    </div>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    {{-- Card 1: Account Selection --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Select Account</h2>
        
        @if($impersonatedAccount)
        <div>
            <label class="block text-sm font-medium mb-2">Account *</label>
            <div class="flex items-center gap-3">
                <input type="text" value="{{ $impersonatedAccount->account_display_name }} ({{ $impersonatedAccount->primary_contact_email_address }})" readonly class="flex-1 px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;">
                <a href="{{ route('admin.accounts') }}" class="px-4 py-2 rounded-md font-medium whitespace-nowrap" style="background-color: #9333ea; color: #ffffff;">
                    Change Account
                </a>
            </div>
            <p class="text-xs mt-2 opacity-70">Managing fees for the impersonated account.</p>
        </div>
        @else
        <div>
            <label class="block text-sm font-medium mb-2">Account *</label>
            <select @change="onAccountChange($event)" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                <option value="">Select Account</option>
                @foreach($accounts as $account)
                <option value="{{ $account->id }}" data-hash="{{ $account->record_unique_identifier }}">{{ $account->account_display_name }} ({{ $account->account_type }})</option>
                @endforeach
            </select>
            <p class="text-xs mt-2 opacity-70">Tip: Go to Accounts → Impersonate to select an account for easier management.</p>
        </div>
        @endif
    </div>

    {{-- Card 2: GBP Fees --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);" x-show="accountHash">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">GBP Fees</h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Fixed Fee (GBP)</label>
                <input type="number" x-model="gbpFixedFee" step="0.01" min="0" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
                <p class="text-xs mt-1 opacity-70">Flat fee charged per transaction</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Percentage Fee (%)</label>
                <input type="number" x-model="gbpPercentageFee" step="0.01" min="0" max="100" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
                <p class="text-xs mt-1 opacity-70">Percentage of transaction amount</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Minimum Fee (GBP)</label>
                <input type="number" x-model="gbpMinimumFee" step="0.01" min="0" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
                <p class="text-xs mt-1 opacity-70">Minimum fee charged regardless of calculation</p>
            </div>
            
            <div class="pt-2">
                <button type="button" 
                        @click="saveFees('GBP')" 
                        :disabled="saving"
                        class="w-full px-6 py-2 rounded-md font-medium hover:opacity-80 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" 
                        style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                    <svg x-show="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Save GBP Fees</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Card 3: EUR Fees --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);" x-show="accountHash">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">EUR Fees</h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Fixed Fee (EUR)</label>
                <input type="number" x-model="eurFixedFee" step="0.01" min="0" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
                <p class="text-xs mt-1 opacity-70">Flat fee charged per transaction</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Percentage Fee (%)</label>
                <input type="number" x-model="eurPercentageFee" step="0.01" min="0" max="100" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
                <p class="text-xs mt-1 opacity-70">Percentage of transaction amount</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Minimum Fee (EUR)</label>
                <input type="number" x-model="eurMinimumFee" step="0.01" min="0" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
                <p class="text-xs mt-1 opacity-70">Minimum fee charged regardless of calculation</p>
            </div>
            
            <div class="pt-2">
                <button type="button" 
                        @click="saveFees('EUR')" 
                        :disabled="saving"
                        class="w-full px-6 py-2 rounded-md font-medium hover:opacity-80 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" 
                        style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                    <svg x-show="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Save EUR Fees</span>
                </button>
            </div>
        </div>
    </div>

    {{-- No Account Selected Message --}}
    <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);" x-show="!accountHash">
        <p class="opacity-70">Please select an account above to manage fees.</p>
    </div>
</div>
@endsection
