@extends('layouts.platform')

@section('content')
<style>[x-cloak] { display: none !important; }</style>
<script>
function ibanData() {
    return {
        csrfToken: '{{ csrf_token() }}',
        accountHash: '{{ $impersonatedAccount?->record_unique_identifier ?? '' }}',
        ibans: [],
        hostBanks: [],
        selectedIbanHash: '',
        isNewIban: true,
        loading: false,
        saving: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        
        // Form fields
        friendlyName: '',
        ibanLedger: '',
        currency: 'EUR',
        ibanNumber: '',
        bicRouting: '',
        ibanOwner: '',
        hostBankHash: '',
        isActive: true,
        
        async init() {
            await this.loadHostBanks();
            if (this.accountHash) {
                await this.loadIbans();
            }
        },
        
        async loadHostBanks() {
            try {
                const response = await fetch('{{ route('admin.iban-host-banks.list') }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.hostBanks = data.host_banks.filter(b => b.is_active);
                }
            } catch (error) {
                console.error('Error loading host banks:', error);
            }
        },
        
        async loadIbans() {
            if (!this.accountHash) return;
            
            this.loading = true;
            try {
                const response = await fetch(`{{ url('/administrator/ibans/list') }}?account_hash=${this.accountHash}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.ibans = data.ibans;
                }
            } catch (error) {
                console.error('Error loading IBANs:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async onIbanSelect() {
            if (this.selectedIbanHash === '' || this.selectedIbanHash === 'new') {
                this.isNewIban = true;
                this.resetForm();
                return;
            }
            
            this.isNewIban = false;
            this.loading = true;
            
            try {
                const response = await fetch(`{{ url('/administrator/ibans') }}/${this.selectedIbanHash}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.friendlyName = data.iban.friendly_name;
                    this.ibanLedger = data.iban.iban_ledger || '';
                    this.currency = data.iban.currency;
                    this.ibanNumber = data.iban.iban_number;
                    this.bicRouting = data.iban.bic_routing || '';
                    this.ibanOwner = data.iban.iban_owner || '';
                    this.hostBankHash = data.iban.host_bank_hash || '';
                    this.isActive = data.iban.is_active;
                }
            } catch (error) {
                console.error('Error loading IBAN:', error);
                this.showNotification('Error loading IBAN details', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        resetForm() {
            this.friendlyName = '';
            this.ibanLedger = '';
            this.currency = 'EUR';
            this.ibanNumber = '';
            this.bicRouting = '';
            this.ibanOwner = '';
            this.hostBankHash = '';
            this.isActive = true;
        },
        
        async saveIban() {
            if (!this.accountHash) {
                this.showNotification('Please select an account first', 'error');
                return;
            }
            
            if (!this.friendlyName || !this.ibanNumber) {
                this.showNotification('Please fill in required fields', 'error');
                return;
            }
            
            this.saving = true;
            
            const formData = {
                account_hash: this.accountHash,
                iban_friendly_name: this.friendlyName,
                iban_ledger: this.ibanLedger || null,
                iban_currency_iso3: this.currency,
                iban_number: this.ibanNumber,
                bic_routing: this.bicRouting || null,
                iban_owner: this.ibanOwner || null,
                iban_host_bank_hash: this.hostBankHash || null,
                is_active: this.isActive,
            };
            
            try {
                let url, method;
                if (this.isNewIban) {
                    url = '{{ route('admin.ibans.store') }}';
                    method = 'POST';
                } else {
                    url = `{{ url('/administrator/ibans') }}/${this.selectedIbanHash}`;
                    method = 'PUT';
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
                    await this.loadIbans();
                    
                    if (this.isNewIban && data.iban) {
                        this.selectedIbanHash = data.iban.hash;
                        this.isNewIban = false;
                    }
                } else {
                    this.showNotification(data.message || 'Error saving IBAN', 'error');
                }
            } catch (error) {
                console.error('Error saving IBAN:', error);
                this.showNotification('Network error while saving', 'error');
            } finally {
                this.saving = false;
            }
        },
        
        async deleteIban() {
            if (this.isNewIban || !this.selectedIbanHash) {
                return;
            }
            
            if (!confirm('Are you sure you want to delete this IBAN? This action cannot be undone.')) {
                return;
            }
            
            this.saving = true;
            
            try {
                const response = await fetch(`{{ url('/administrator/ibans') }}/${this.selectedIbanHash}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('IBAN deleted successfully', 'success');
                    this.selectedIbanHash = '';
                    this.isNewIban = true;
                    this.resetForm();
                    await this.loadIbans();
                } else {
                    this.showNotification(data.message || 'Error deleting IBAN', 'error');
                }
            } catch (error) {
                console.error('Error deleting IBAN:', error);
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
            this.selectedIbanHash = '';
            this.isNewIban = true;
            this.resetForm();
            this.ibans = [];
            if (this.accountHash) {
                this.loadIbans();
            }
        }
    };
}
</script>

<div class="max-w-4xl mx-auto" x-data="ibanData()">
    
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
        <h1 class="text-2xl font-bold">IBAN Management</h1>
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
            <p class="text-xs mt-2 opacity-70">Managing IBANs for the impersonated account.</p>
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

    {{-- Card 2: IBAN Selector --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);" x-show="accountHash">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Select IBAN</h2>
        
        <div>
            <label class="block text-sm font-medium mb-2">Choose IBAN to Edit or Add New</label>
            <select x-model="selectedIbanHash" @change="onIbanSelect()" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                <option value="new">+ ADD NEW IBAN</option>
                <template x-for="iban in ibans" :key="iban.hash">
                    <option :value="iban.hash" x-text="`${iban.friendly_name} (${iban.currency}) - ${iban.iban_number}`"></option>
                </template>
            </select>
            <p class="text-xs mt-2 opacity-70" x-show="ibans.length === 0 && !loading">No existing IBANs found. Create a new one below.</p>
            <p class="text-xs mt-2 opacity-70" x-show="loading">Loading IBANs...</p>
        </div>
    </div>

    {{-- Card 3: IBAN Form --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);" x-show="accountHash">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);" x-text="isNewIban ? 'Add New IBAN' : 'Edit IBAN'"></h2>
        
        <div class="space-y-4">
            {{-- Friendly Name --}}
            <div>
                <label class="block text-sm font-medium mb-2">IBAN Friendly Name *</label>
                <input type="text" x-model="friendlyName" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="e.g., Main Business Account">
            </div>
            
            {{-- IBAN Ledger (Admin-only, internal reference) --}}
            <div>
                <label class="block text-sm font-medium mb-2">IBAN Ledger</label>
                <input type="text" x-model="ibanLedger" class="w-full px-3 py-2 rounded-md font-mono" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="e.g., 3fa85f64-5717-4562-b3fc-2c963f66afa6" maxlength="36">
                <p class="text-xs mt-1 opacity-70">SH Financial Ledger UUID - internal reference for SEPA Direct Debit processing</p>
            </div>
            
            {{-- Currency --}}
            <div>
                <label class="block text-sm font-medium mb-2">IBAN Currency *</label>
                <select x-model="currency" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    <option value="AUD">AUD - Australian Dollar</option>
                    <option value="CNY">CNY - Chinese Yuan</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="MXN">MXN - Mexican Peso</option>
                    <option value="USD">USD - US Dollar</option>
                </select>
            </div>
            
            {{-- IBAN Number --}}
            <div>
                <label class="block text-sm font-medium mb-2">IBAN Number *</label>
                <input type="text" x-model="ibanNumber" class="w-full px-3 py-2 rounded-md font-mono" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="e.g., DE89370400440532013000">
            </div>
            
            {{-- BIC Routing --}}
            <div>
                <label class="block text-sm font-medium mb-2">BIC Routing</label>
                <input type="text" x-model="bicRouting" class="w-full px-3 py-2 rounded-md font-mono" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="e.g., DEUTDEFF" maxlength="11">
                <p class="text-xs mt-1 opacity-70">Bank Identifier Code (BIC/SWIFT) - typically 8 or 11 characters</p>
            </div>
            
            {{-- IBAN Owner --}}
            <div>
                <label class="block text-sm font-medium mb-2">IBAN Owner</label>
                <input type="text" x-model="ibanOwner" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="e.g., John Doe" maxlength="255">
                <p class="text-xs mt-1 opacity-70">Name of the account holder</p>
            </div>
            
            {{-- Host Bank --}}
            <div>
                <label class="block text-sm font-medium mb-2">IBAN Host Bank</label>
                <select x-model="hostBankHash" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    <option value="">-- Select Host Bank --</option>
                    <template x-for="bank in hostBanks" :key="bank.hash">
                        <option :value="bank.hash" x-text="bank.name"></option>
                    </template>
                </select>
                <p class="text-xs mt-1 opacity-70">
                    <a href="{{ route('admin.iban-host-banks') }}" class="underline hover:opacity-80">Manage Host Banks</a>
                </p>
            </div>
            
            {{-- Status Toggle --}}
            <div>
                <label class="block text-sm font-medium mb-2">IBAN Status</label>
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
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);" x-show="accountHash">
        <div class="flex justify-between items-center">
            <div>
                <button type="button" 
                        x-show="!isNewIban"
                        @click="deleteIban()" 
                        :disabled="saving"
                        class="px-4 py-2 rounded-md font-medium hover:opacity-80 flex items-center gap-2" 
                        style="background-color: #dc2626; color: white;">
                    <svg x-show="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Soft Delete</span>
                </button>
            </div>
            
            <button type="button" 
                    @click="saveIban()" 
                    :disabled="saving || !friendlyName || !ibanNumber"
                    class="px-6 py-2 rounded-md font-medium hover:opacity-80 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" 
                    style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                <svg x-show="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isNewIban ? 'Create IBAN' : 'Update IBAN'"></span>
            </button>
        </div>
    </div>

    {{-- No Account Selected Message --}}
    <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);" x-show="!accountHash">
        <p class="opacity-70">Please select an account above to manage IBANs.</p>
    </div>
</div>
@endsection
