@extends('layouts.platform')

@section('content')
<style>[x-cloak] { display: none !important; }</style>
<script>
function hostBankData() {
    return {
        csrfToken: '{{ csrf_token() }}',
        hostBanks: [],
        selectedBankHash: '',
        isNewBank: true,
        loading: false,
        saving: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        
        // Form fields
        bankName: '',
        isActive: true,
        
        async init() {
            await this.loadHostBanks();
        },
        
        async loadHostBanks() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.iban-host-banks.list') }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.hostBanks = data.host_banks;
                }
            } catch (error) {
                console.error('Error loading host banks:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async onBankSelect() {
            if (this.selectedBankHash === '' || this.selectedBankHash === 'new') {
                this.isNewBank = true;
                this.resetForm();
                return;
            }
            
            this.isNewBank = false;
            this.loading = true;
            
            try {
                const response = await fetch(`{{ url('/administrator/iban-host-banks') }}/${this.selectedBankHash}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.bankName = data.host_bank.name;
                    this.isActive = data.host_bank.is_active;
                }
            } catch (error) {
                console.error('Error loading host bank:', error);
                this.showNotification('Error loading host bank details', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        resetForm() {
            this.bankName = '';
            this.isActive = true;
        },
        
        async saveBank() {
            if (!this.bankName) {
                this.showNotification('Please enter a host bank name', 'error');
                return;
            }
            
            this.saving = true;
            
            const formData = {
                host_bank_name: this.bankName,
                is_active: this.isActive,
            };
            
            try {
                let url, method;
                if (this.isNewBank) {
                    url = '{{ route('admin.iban-host-banks.store') }}';
                    method = 'POST';
                } else {
                    url = `{{ url('/administrator/iban-host-banks') }}/${this.selectedBankHash}`;
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
                    await this.loadHostBanks();
                    
                    if (this.isNewBank && data.host_bank) {
                        this.selectedBankHash = data.host_bank.hash;
                        this.isNewBank = false;
                    }
                } else {
                    this.showNotification(data.message || 'Error saving host bank', 'error');
                }
            } catch (error) {
                console.error('Error saving host bank:', error);
                this.showNotification('Network error while saving', 'error');
            } finally {
                this.saving = false;
            }
        },
        
        async deleteBank() {
            if (this.isNewBank || !this.selectedBankHash) {
                return;
            }
            
            if (!confirm('Are you sure you want to delete this host bank? This may affect IBANs using this host bank.')) {
                return;
            }
            
            this.saving = true;
            
            try {
                const response = await fetch(`{{ url('/administrator/iban-host-banks') }}/${this.selectedBankHash}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Host bank deleted successfully', 'success');
                    this.selectedBankHash = '';
                    this.isNewBank = true;
                    this.resetForm();
                    await this.loadHostBanks();
                } else {
                    this.showNotification(data.message || 'Error deleting host bank', 'error');
                }
            } catch (error) {
                console.error('Error deleting host bank:', error);
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
        }
    };
}
</script>

<div class="max-w-4xl mx-auto" x-data="hostBankData()">
    
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
        <h1 class="text-2xl font-bold">IBAN Host Banks</h1>
        <a href="{{ route('admin.index') }}" class="px-4 py-2 rounded-md hover:opacity-80" style="background-color: var(--card-background-color);">
            ← Back to Admin
        </a>
    </div>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    {{-- Card 1: Host Bank Selector --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Select Host Bank</h2>
        
        <div>
            <label class="block text-sm font-medium mb-2">Choose Host Bank to Edit or Add New</label>
            <select x-model="selectedBankHash" @change="onBankSelect()" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                <option value="new">+ ADD NEW HOST BANK</option>
                <template x-for="bank in hostBanks" :key="bank.hash">
                    <option :value="bank.hash" x-text="bank.name"></option>
                </template>
            </select>
            <p class="text-xs mt-2 opacity-70" x-show="hostBanks.length === 0 && !loading">No host banks found. Create one below.</p>
            <p class="text-xs mt-2 opacity-70" x-show="loading">Loading host banks...</p>
        </div>
    </div>

    {{-- Card 2: Host Bank Form --}}
    <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);" x-text="isNewBank ? 'Add New Host Bank' : 'Edit Host Bank'"></h2>
        
        <div class="space-y-4">
            {{-- Host Bank Name --}}
            <div>
                <label class="block text-sm font-medium mb-2">Host Bank Name *</label>
                <input type="text" x-model="bankName" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="e.g., Deutsche Bank, Barclays, HSBC">
            </div>
            
            {{-- Status Toggle --}}
            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
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
                        INACTIVE
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 3: Actions --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <div class="flex justify-between items-center">
            <div>
                <button type="button" 
                        x-show="!isNewBank"
                        @click="deleteBank()" 
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
                    @click="saveBank()" 
                    :disabled="saving || !bankName"
                    class="px-6 py-2 rounded-md font-medium hover:opacity-80 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" 
                    style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                <svg x-show="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isNewBank ? 'Create Host Bank' : 'Update Host Bank'"></span>
            </button>
        </div>
    </div>

    {{-- Existing Host Banks List --}}
    <div class="rounded-lg p-6 mt-4" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Existing Host Banks</h2>
        
        <div x-show="loading" class="text-center py-4 opacity-70">Loading...</div>
        
        <div x-show="!loading && hostBanks.length === 0" class="text-center py-4 opacity-70">
            No host banks found. Create one above.
        </div>
        
        <div x-show="!loading && hostBanks.length > 0" class="space-y-2">
            <template x-for="bank in hostBanks" :key="bank.hash">
                <div class="flex items-center justify-between p-3 rounded-md" style="background-color: var(--content-background-color);">
                    <div class="flex items-center gap-3">
                        <span class="font-medium" x-text="bank.name"></span>
                        <span class="text-xs px-2 py-1 rounded" 
                              :style="bank.is_active ? 'background-color: var(--brand-primary-color); color: #1a1a2e;' : 'background-color: #f59e0b; color: #1a1a2e;'"
                              x-text="bank.is_active ? 'ACTIVE' : 'INACTIVE'"></span>
                    </div>
                    <button @click="selectedBankHash = bank.hash; onBankSelect()" class="text-sm underline opacity-70 hover:opacity-100">Edit</button>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
