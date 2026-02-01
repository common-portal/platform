@extends('layouts.platform')

@section('content')
    <style>[x-cloak] { display: none !important; }</style>
    <script>
    function accountingData() {
        return {
        transactionId: null,
        transactionHash: null,
        showToast: false,
        tenantAccountId: {{ session('active_account_id') ?? 'null' }},
        csrfToken: '{{ csrf_token() }}',
        settlementType: 'crypto',
        status: 'received',
        isEditMode: false,
        receivedAmount: '',
        receivedAmountDisplay: '',
        exchangeRatio: 1,
        settlementAmount: '',
        settlementAmountDisplay: '',
        incomingFixedFee: '',
        incomingPercentageFee: '',
        incomingMinimumFee: '',
        incomingTotalFee: 0,
        incomingTotalFeeDisplay: '0.00000',
        netIncomingAmount: 0,
        netIncomingAmountDisplay: '0.00000',
        incomingCurrencyCode: 'EUR',
        exchangeFixedFee: '',
        exchangePercentageFee: '',
        exchangeMinimumFee: '',
        exchangeTotalFee: 0,
        exchangeTotalFeeDisplay: '0.00000',
        netExchangeAmount: 0,
        netExchangeAmountDisplay: '0.00000',
        outgoingFixedFee: '',
        outgoingPercentageFee: '',
        outgoingMinimumFee: '',
        outgoingTotalFee: 0,
        outgoingTotalFeeDisplay: '0.00000',
        finalSettlementAmount: 0,
        finalSettlementAmountDisplay: '0.00000',
        settlementCurrencyCode: 'EURC',
        datetimeReceived: '',
        datetimeExchanged: '',
        datetimeSettled: '',
        phase1Enabled: true,
        phase2Enabled: false,
        phase3Enabled: false,
        loadingReceived: false,
        loadingExchanged: false,
        loadingSettled: false,
        showSuccessToast: false,
        errorMessage: '',
        getCurrentDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        },
        async setStatusReceived() {
            this.loadingReceived = true;
            this.datetimeReceived = this.getCurrentDateTime();
            
            const formData = {
                tenant_account_id: this.tenantAccountId,
                currency_code: this.incomingCurrencyCode,
                amount: this.receivedAmount,
                incoming_fixed_fee: this.incomingFixedFee || 0,
                incoming_percentage_fee: this.incomingPercentageFee || 0,
                incoming_minimum_fee: this.incomingMinimumFee || 0,
                incoming_total_fee: this.incomingTotalFee || 0,
                datetime_received: this.datetimeReceived,
            };
            
            try {
                const response = await fetch('{{ route('admin.transactions.phase1') }}', {
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
                    this.transactionId = data.transaction_id;
                    this.status = 'received';
                    this.phase1Enabled = false;
                    this.phase2Enabled = true;
                } else {
                    this.errorMessage = data.message || 'Failed to record Phase 1';
                    alert(this.errorMessage);
                }
            } catch (error) {
                console.error('Phase 1 error:', error);
                this.errorMessage = 'Network error during Phase 1';
                alert(this.errorMessage);
            } finally {
                this.loadingReceived = false;
            }
        },
        async setStatusExchanged() {
            if (!this.transactionId) {
                alert('Error: No transaction record found. Please complete Phase 1 first.');
                return;
            }
            
            this.loadingExchanged = true;
            this.datetimeExchanged = this.getCurrentDateTime();
            
            const formData = {
                tenant_account_id: this.tenantAccountId,
                settlement_currency_code: this.settlementCurrencyCode,
                exchange_ratio: this.exchangeRatio,
                settlement_amount: this.settlementAmount,
                exchange_fixed_fee: this.exchangeFixedFee || 0,
                exchange_percentage_fee: this.exchangePercentageFee || 0,
                exchange_minimum_fee: this.exchangeMinimumFee || 0,
                exchange_total_fee: this.exchangeTotalFee || 0,
                datetime_exchanged: this.datetimeExchanged,
            };
            
            try {
                const response = await fetch(`{{ url('/administrator/transactions/phase2') }}/${this.transactionId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.status = 'exchanged';
                    this.phase2Enabled = false;
                    this.phase3Enabled = true;
                } else {
                    this.errorMessage = data.message || 'Failed to record Phase 2';
                    alert(this.errorMessage);
                }
            } catch (error) {
                console.error('Phase 2 error:', error);
                this.errorMessage = 'Network error during Phase 2';
                alert(this.errorMessage);
            } finally {
                this.loadingExchanged = false;
            }
        },
        async setStatusSettled() {
            if (!this.transactionId) {
                alert('Error: No transaction record found. Please complete Phase 1 and 2 first.');
                return;
            }
            
            this.loadingSettled = true;
            this.datetimeSettled = this.getCurrentDateTime();
            
            const formData = {
                tenant_account_id: this.tenantAccountId,
                outgoing_fixed_fee: this.outgoingFixedFee || 0,
                outgoing_percentage_fee: this.outgoingPercentageFee || 0,
                outgoing_minimum_fee: this.outgoingMinimumFee || 0,
                outgoing_total_fee: this.outgoingTotalFee || 0,
                final_settlement_currency_code: this.settlementCurrencyCode,
                final_settlement_amount: this.finalSettlementAmount,
                settlement_account_type: this.settlementType,
                crypto_wallet_address: this.settlementType === 'crypto' ? document.querySelector('[name="crypto_wallet_address"]')?.value : null,
                crypto_network: this.settlementType === 'crypto' ? document.querySelector('[name="crypto_network"]')?.value : null,
                fiat_payment_method: this.settlementType === 'fiat' ? document.querySelector('[name="fiat_payment_method"]')?.value : null,
                fiat_bank_account_number: this.settlementType === 'fiat' ? document.querySelector('[name="fiat_bank_account_number"]')?.value : null,
                fiat_bank_routing_number: document.querySelector('[name="fiat_bank_routing_number"]')?.value || null,
                fiat_bank_swift_code: document.querySelector('[name="fiat_bank_swift_code"]')?.value || null,
                fiat_account_holder_name: this.settlementType === 'fiat' ? document.querySelector('[name="fiat_account_holder_name"]')?.value : null,
                fiat_bank_address: document.querySelector('[name="fiat_bank_address"]')?.value || null,
                fiat_bank_country: this.settlementType === 'fiat' ? document.querySelector('[name="fiat_bank_country"]')?.value : null,
                datetime_settled: this.datetimeSettled,
            };
            
            try {
                const response = await fetch(`{{ url('/administrator/transactions/phase3') }}/${this.transactionId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.status = 'settled';
                    // Dispatch event to trigger toast
                    window.dispatchEvent(new CustomEvent('submit-success'));
                } else {
                    this.errorMessage = data.message || 'Failed to record Phase 3';
                    alert(this.errorMessage);
                }
            } catch (error) {
                console.error('Phase 3 error:', error);
                this.errorMessage = 'Network error during Phase 3';
                alert(this.errorMessage);
            } finally {
                this.loadingSettled = false;
            }
        },
        formatNumber(value) {
            if (!value) return '';
            const parts = value.toString().split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        },
        unformatNumber(value) {
            return value.replace(/,/g, '');
        },
        updateReceivedAmount() {
            const unformatted = this.unformatNumber(this.receivedAmountDisplay);
            this.receivedAmount = unformatted;
            this.calculateSettlementAmount();
            this.calculateIncomingFees();
        },
        formatReceivedAmountDisplay() {
            if (this.receivedAmountDisplay) {
                const unformatted = this.unformatNumber(this.receivedAmountDisplay);
                const numValue = parseFloat(unformatted) || 0;
                const fixedValue = numValue.toFixed(5);
                this.receivedAmountDisplay = this.formatNumber(fixedValue);
            }
        },
        updateSettlementAmount() {
            const unformatted = this.unformatNumber(this.settlementAmountDisplay);
            this.settlementAmount = unformatted;
            this.calculateExchangeRatio();
            this.calculateExchangeFees();
        },
        formatSettlementAmountDisplay() {
            if (this.settlementAmountDisplay) {
                const unformatted = this.unformatNumber(this.settlementAmountDisplay);
                const numValue = parseFloat(unformatted) || 0;
                const fixedValue = numValue.toFixed(5);
                this.settlementAmountDisplay = this.formatNumber(fixedValue);
            }
        },
        calculateSettlementAmount() {
            const amount = parseFloat(this.receivedAmount) || 0;
            const incomingFee = parseFloat(this.incomingTotalFee) || 0;
            const netAmount = amount - incomingFee;
            const ratio = parseFloat(this.exchangeRatio) || 1;
            this.settlementAmount = (netAmount * ratio).toFixed(5);
            this.settlementAmountDisplay = this.formatNumber(this.settlementAmount);
            this.calculateExchangeFees();
        },
        calculateExchangeRatio() {
            const amount = parseFloat(this.receivedAmount) || 0;
            const settlement = parseFloat(this.settlementAmount) || 0;
            if (amount > 0) {
                this.exchangeRatio = (settlement / amount).toFixed(8);
            }
        },
        calculateIncomingFees() {
            const fixed = parseFloat(this.incomingFixedFee) || 0;
            const percentage = parseFloat(this.incomingPercentageFee) || 0;
            const minimum = parseFloat(this.incomingMinimumFee) || 0;
            const amount = parseFloat(this.receivedAmount) || 0;
            const percentageFee = (amount * percentage / 100);
            const total = fixed + percentageFee;
            this.incomingTotalFee = Math.max(total, minimum).toFixed(5);
            this.incomingTotalFeeDisplay = this.formatNumber(this.incomingTotalFee);
            this.calculateNetIncomingAmount();
            this.calculateSettlementAmount();
        },
        calculateNetIncomingAmount() {
            const amount = parseFloat(this.receivedAmount) || 0;
            const incomingFee = parseFloat(this.incomingTotalFee) || 0;
            this.netIncomingAmount = (amount - incomingFee).toFixed(5);
            this.netIncomingAmountDisplay = this.formatNumber(this.netIncomingAmount);
        },
        calculateExchangeFees() {
            const fixed = parseFloat(this.exchangeFixedFee) || 0;
            const percentage = parseFloat(this.exchangePercentageFee) || 0;
            const minimum = parseFloat(this.exchangeMinimumFee) || 0;
            const amount = parseFloat(this.settlementAmount) || 0;
            const percentageFee = (amount * percentage / 100);
            const total = fixed + percentageFee;
            this.exchangeTotalFee = Math.max(total, minimum).toFixed(5);
            this.exchangeTotalFeeDisplay = this.formatNumber(this.exchangeTotalFee);
            this.calculateNetExchangeAmount();
        },
        calculateNetExchangeAmount() {
            const grossAmount = parseFloat(this.settlementAmount) || 0;
            const exchangeFee = parseFloat(this.exchangeTotalFee) || 0;
            this.netExchangeAmount = (grossAmount - exchangeFee).toFixed(5);
            this.netExchangeAmountDisplay = this.formatNumber(this.netExchangeAmount);
        },
        calculateOutgoingFees() {
            const fixed = parseFloat(this.outgoingFixedFee) || 0;
            const percentage = parseFloat(this.outgoingPercentageFee) || 0;
            const minimum = parseFloat(this.outgoingMinimumFee) || 0;
            const amount = parseFloat(this.settlementAmount) || 0;
            const percentageFee = (amount * percentage / 100);
            const total = fixed + percentageFee;
            this.outgoingTotalFee = Math.max(total, minimum).toFixed(5);
            this.outgoingTotalFeeDisplay = this.formatNumber(this.outgoingTotalFee);
            this.calculateFinalSettlementAmount();
        },
        calculateFinalSettlementAmount() {
            const settlement = parseFloat(this.settlementAmount) || 0;
            const outgoingFee = parseFloat(this.outgoingTotalFee) || 0;
            this.finalSettlementAmount = (settlement - outgoingFee).toFixed(5);
            this.finalSettlementAmountDisplay = this.formatNumber(this.finalSettlementAmount);
        },
        async init() {
            // Check for transaction_hash in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const transactionHash = urlParams.get('transaction_hash');
            
            if (transactionHash) {
                await this.loadTransaction(transactionHash);
            }
        },
        async loadTransaction(transactionHash) {
            try {
                const response = await fetch(`/administrator/accounting/transaction/${transactionHash}?tenant_account_id=${this.tenantAccountId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const txn = data.transaction;
                    this.isEditMode = true;
                    this.transactionId = txn.id;
                    this.transactionHash = txn.record_unique_identifier;
                    
                    // Populate Phase 1 fields
                    this.incomingCurrencyCode = txn.currency_code || 'EUR';
                    this.receivedAmount = txn.amount || '';
                    this.receivedAmountDisplay = this.formatNumber(txn.amount || '');
                    this.incomingFixedFee = txn.incoming_fixed_fee || '';
                    this.incomingPercentageFee = txn.incoming_percentage_fee || '';
                    this.incomingMinimumFee = txn.incoming_minimum_fee || '';
                    this.incomingTotalFee = txn.incoming_total_fee || 0;
                    this.incomingTotalFeeDisplay = this.formatNumber(txn.incoming_total_fee || 0);
                    this.datetimeReceived = txn.datetime_received || '';
                    
                    // Populate Phase 2 fields if exchanged
                    if (txn.datetime_exchanged) {
                        this.settlementCurrencyCode = txn.settlement_currency_code || 'EURC';
                        this.exchangeRatio = txn.exchange_ratio || 1;
                        this.settlementAmount = txn.settlement_amount || '';
                        this.settlementAmountDisplay = this.formatNumber(txn.settlement_amount || '');
                        this.exchangeFixedFee = txn.exchange_fixed_fee || '';
                        this.exchangePercentageFee = txn.exchange_percentage_fee || '';
                        this.exchangeMinimumFee = txn.exchange_minimum_fee || '';
                        this.exchangeTotalFee = txn.exchange_total_fee || 0;
                        this.exchangeTotalFeeDisplay = this.formatNumber(txn.exchange_total_fee || 0);
                        this.datetimeExchanged = txn.datetime_exchanged || '';
                        this.phase1Enabled = false;
                        this.phase2Enabled = false;
                        this.phase3Enabled = true;
                        this.status = 'exchanged';
                    } else {
                        // Phase 1 complete, enable Phase 2
                        this.phase1Enabled = false;
                        this.phase2Enabled = true;
                        this.status = 'received';
                    }
                    
                    // Populate Phase 3 fields if settled
                    if (txn.datetime_settled) {
                        this.settlementType = txn.settlement_account_type || 'crypto';
                        this.outgoingFixedFee = txn.outgoing_fixed_fee || '';
                        this.outgoingPercentageFee = txn.outgoing_percentage_fee || '';
                        this.outgoingMinimumFee = txn.outgoing_minimum_fee || '';
                        this.outgoingTotalFee = txn.outgoing_total_fee || 0;
                        this.outgoingTotalFeeDisplay = this.formatNumber(txn.outgoing_total_fee || 0);
                        this.finalSettlementAmount = txn.final_settlement_amount || 0;
                        this.finalSettlementAmountDisplay = this.formatNumber(txn.final_settlement_amount || 0);
                        this.datetimeSettled = txn.datetime_settled || '';
                        this.phase1Enabled = false;
                        this.phase2Enabled = false;
                        this.phase3Enabled = false;
                        this.status = 'settled';
                    }
                    
                    // Recalculate display values
                    this.calculateIncomingFees();
                    this.calculateExchangeFees();
                    this.calculateOutgoingFees();
                } else {
                    alert('Error loading transaction: ' + data.message);
                }
            } catch (error) {
                console.error('Load transaction error:', error);
                alert('Failed to load transaction data');
            }
        },
        viewTransaction() {
            if (this.transactionHash) {
                window.location.href = `/modules/transactions?transaction_id=${this.transactionHash}`;
            }
        }
        };
    }
    </script>

    <div class="max-w-6xl mx-auto" x-data="accountingData()" @submit-success.window="showToast = true; setTimeout(() => showToast = false, 5000)">
        @csrf

        {{-- Success Toast --}}
        <div x-cloak
             x-show="showToast" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed top-4 right-4 z-50 max-w-md rounded-lg p-4 shadow-lg" 
             style="background-color: var(--brand-primary-color); color: #1a1a2e;">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">Transaction Settled Successfully!</span>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Accounting - Record Transaction</h1>
            <div class="flex gap-3">
                <a href="{{ route('admin.index') }}" class="px-4 py-2 rounded-md hover:opacity-80" style="background-color: var(--card-background-color);">
                    ← Back to Admin
                </a>
                <a :href="transactionHash ? `/modules/transactions?transaction_id=${transactionHash}` : '#'" x-show="transactionHash" class="px-4 py-2 rounded-md font-semibold hover:opacity-80" style="background-color: #ef4444; color: white;">
                    VIEW TRANSACTION
                </a>
            </div>
        </div>

        @if(session('status'))
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
            {{ session('status') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Card 1: Active Impersonated Account --}}
        <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Active Impersonated Account</h2>
            @if($impersonatedAccount)
            <div>
                <label class="block text-sm font-medium mb-2">Account *</label>
                <div class="flex items-center gap-3">
                    <input type="text" value="{{ $impersonatedAccount->account_display_name }} ({{ $impersonatedAccount->primary_contact_email_address }})" readonly class="flex-1 px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;">
                    <input type="hidden" name="tenant_account_id" value="{{ $impersonatedAccount->id }}">
                    <a href="{{ route('admin.accounts') }}" class="px-4 py-2 rounded-md font-medium whitespace-nowrap" style="background-color: #9333ea; color: #ffffff;">
                        Change Account
                    </a>
                </div>
                <p class="text-xs mt-2 opacity-70">Currently managing transactions for the impersonated account. Click "Change Account" to select a different account.</p>
            </div>
            @else
            <div>
                <label class="block text-sm font-medium mb-2">Account *</label>
                <select name="tenant_account_id" required class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    <option value="">Select Account</option>
                    @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->account_display_name }} ({{ $account->account_type }})</option>
                    @endforeach
                </select>
                <p class="text-xs mt-2 opacity-70">Tip: Go to Accounts → Impersonate to select an account for easier transaction management.</p>
            </div>
            @endif
        </div>

        {{-- Phase 1: Receiving Funds --}}
        <div class="rounded-lg p-6 mb-6 transition-opacity" :class="!phase1Enabled && 'opacity-50 pointer-events-none'" style="background-color: rgba(147, 51, 234, 0.1); border: 2px solid #9333ea;">
            <h1 class="text-2xl font-bold mb-6" style="color: #ffffff;">Phase 1: Receiving Funds</h1>
            
            {{-- Card 2: Incoming Funds --}}
            <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Incoming Funds</h2>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Incoming Currency *</label>
                <select name="currency_code" x-model="incomingCurrencyCode" required class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    <optgroup label="Fiat">
                        <option value="AUD">AUD - Australian Dollar</option>
                        <option value="BRL">BRL - Brazilian Real</option>
                        <option value="CAD">CAD - Canadian Dollar</option>
                        <option value="CHF">CHF - Swiss Franc</option>
                        <option value="CNY">CNY - Chinese Yuan</option>
                        <option value="EUR" selected>EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                        <option value="HKD">HKD - Hong Kong Dollar</option>
                        <option value="INR">INR - Indian Rupee</option>
                        <option value="JPY">JPY - Japanese Yen</option>
                        <option value="KRW">KRW - South Korean Won</option>
                        <option value="MXN">MXN - Mexican Peso</option>
                        <option value="MYR">MYR - Malaysian Ringgit</option>
                        <option value="NOK">NOK - Norwegian Krone</option>
                        <option value="NZD">NZD - New Zealand Dollar</option>
                        <option value="RUB">RUB - Russian Ruble</option>
                        <option value="SEK">SEK - Swedish Krona</option>
                        <option value="SGD">SGD - Singapore Dollar</option>
                        <option value="TRY">TRY - Turkish Lira</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="VND">VND - Vietnamese Dong</option>
                        <option value="ZAR">ZAR - South African Rand</option>
                    </optgroup>
                    <optgroup label="Stablecoins">
                        <option value="USDT">USDT</option>
                        <option value="USDC">USDC</option>
                        <option value="EURC">EURC</option>
                        <option value="GBPC">GBPC</option>
                    </optgroup>
                    <optgroup label="Major Crypto">
                        <option value="BTC">BTC</option>
                        <option value="ETH">ETH</option>
                        <option value="SOL">SOL</option>
                        <option value="XRP">XRP</option>
                        <option value="ADA">ADA</option>
                        <option value="MATIC">MATIC</option>
                        <option value="AVAX">AVAX</option>
                        <option value="DOT">DOT</option>
                        <option value="LINK">LINK</option>
                        <option value="UNI">UNI</option>
                        <option value="LTC">LTC</option>
                        <option value="BCH">BCH</option>
                        <option value="XLM">XLM</option>
                        <option value="ALGO">ALGO</option>
                        <option value="ATOM">ATOM</option>
                        <option value="TRX">TRX</option>
                    </optgroup>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Incoming Amount (5 decimals) *</label>
                <input type="text" 
                       x-model="receivedAmountDisplay" 
                       @input="updateReceivedAmount()" 
                       @blur="formatReceivedAmountDisplay()" 
                       class="w-full px-3 py-2 rounded-md" 
                       style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" 
                       placeholder="0.00000">
                <input type="hidden" name="amount" x-model="receivedAmount" required>
            </div>
        </div>

        {{-- Card 3: Incoming Fees --}}
        <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Incoming Fees</h2>
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Fixed Fee</label>
                <input type="number" name="incoming_fixed_fee" step="0.00001" x-model="incomingFixedFee" @input="calculateIncomingFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00000">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Percentage Fee (%)</label>
                <input type="number" name="incoming_percentage_fee" step="0.01" x-model="incomingPercentageFee" @input="calculateIncomingFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Minimum Fee</label>
                <input type="number" name="incoming_minimum_fee" step="0.00001" x-model="incomingMinimumFee" @input="calculateIncomingFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00000">
            </div>

            <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                <label class="block text-sm font-medium mb-2">Total Fee</label>
                <input type="text" x-model="incomingTotalFeeDisplay" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--card-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="0.00000">
                <input type="hidden" name="incoming_total_fee" x-model="incomingTotalFee">
            </div>
        </div>

        {{-- Card 4: Net Incoming Amount --}}
        <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Net Incoming Amount</h2>
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Incoming Currency</label>
                <input type="text" x-model="incomingCurrencyCode" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="Select currency in Incoming Funds">
            </div>

            <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                <label class="block text-sm font-medium mb-2">Net Incoming Amount</label>
                <input type="text" x-model="netIncomingAmountDisplay" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--card-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="0.00000">
                <p class="text-xs mt-2 opacity-70">Calculated as: Incoming Amount - Incoming Total Fee</p>
            </div>
        </div>

            {{-- Card 5: Record Received --}}
            <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                <div class="flex justify-center">
                    <button type="button" @click="setStatusReceived()" :disabled="loadingReceived" class="px-6 py-2 rounded-md font-medium hover:opacity-80 flex items-center gap-2" style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                        <svg x-show="loadingReceived" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Record Received</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Phase 2: Exchanging Funds --}}
        <div class="rounded-lg p-6 mb-6 transition-opacity" :class="!phase2Enabled && 'opacity-50 pointer-events-none'" style="background-color: rgba(147, 51, 234, 0.1); border: 2px solid #9333ea;">
            <h1 class="text-2xl font-bold mb-6" style="color: #ffffff;">Phase 2: Exchanging Funds</h1>
            
            {{-- Card 6: Funds Exchanged --}}
            <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Funds Exchanged</h2>
            
            {{-- Settlement Account Type --}}
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Destination / Settlement Account *</label>
                <div class="space-y-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="settlement_account_type" value="crypto" x-model="settlementType" required class="mr-2">
                        <span>Crypto</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="settlement_account_type" value="fiat" x-model="settlementType" required class="mr-2">
                        <span>Fiat</span>
                    </label>
                </div>
            </div>

            {{-- Crypto Asset Selection --}}
            <div x-show="settlementType === 'crypto'" class="mb-6">
                <label class="block text-sm font-medium mb-2">Crypto Asset *</label>
                <select name="settlement_currency_code" x-model="settlementCurrencyCode" x-bind:required="settlementType === 'crypto'" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    <option value="">Select Asset</option>
                    <option value="ADA">Cardano (ADA)</option>
                    <option value="ALGO">Algorand (ALGO)</option>
                    <option value="ATOM">Cosmos (ATOM)</option>
                    <option value="AUDC">Australian Dollar Coin (AUDC)</option>
                    <option value="AVAX">Avalanche (AVAX)</option>
                    <option value="BCH">Bitcoin Cash (BCH)</option>
                    <option value="BNB">BNB (BNB)</option>
                    <option value="BTC">Bitcoin (BTC)</option>
                    <option value="DOGE">Dogecoin (DOGE)</option>
                    <option value="DOT">Polkadot (DOT)</option>
                    <option value="ETH">Ethereum (ETH)</option>
                    <option value="EURC" selected>Euro Coin (EURC)</option>
                    <option value="GBPC">Pound Coin (GBPC)</option>
                    <option value="LINK">Chainlink (LINK)</option>
                    <option value="LTC">Litecoin (LTC)</option>
                    <option value="MATIC">Polygon (MATIC)</option>
                    <option value="SOL">Solana (SOL)</option>
                    <option value="TRX">TRON (TRX)</option>
                    <option value="UNI">Uniswap (UNI)</option>
                    <option value="USDC">USD Coin (USDC)</option>
                    <option value="USDT">Tether (USDT)</option>
                    <option value="XLM">Stellar (XLM)</option>
                    <option value="XRP">Ripple (XRP)</option>
                </select>
            </div>

            {{-- Fiat Currency Selection --}}
            <div x-show="settlementType === 'fiat'" class="mb-6">
                <label class="block text-sm font-medium mb-2">Currency *</label>
                <select name="settlement_currency_code" x-model="settlementCurrencyCode" x-bind:required="settlementType === 'fiat'" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    <option value="">Select Currency</option>
                    <option value="AUD">Australian Dollar (AUD)</option>
                    <option value="CAD">Canadian Dollar (CAD)</option>
                    <option value="CHF">Swiss Franc (CHF)</option>
                    <option value="CNY">Chinese Yuan (CNY)</option>
                    <option value="EUR">Euro (EUR)</option>
                    <option value="GBP">British Pound (GBP)</option>
                    <option value="JPY">Japanese Yen (JPY)</option>
                    <option value="MXN">Mexican Peso (MXN)</option>
                    <option value="MYR">Malaysian Ringgit (MYR)</option>
                    <option value="NZD">New Zealand Dollar (NZD)</option>
                    <option value="SEK">Swedish Krona (SEK)</option>
                    <option value="SGD">Singapore Dollar (SGD)</option>
                    <option value="USD">US Dollar (USD)</option>
                    <option value="VND">Vietnamese Dong (VND)</option>
                </select>
            </div>

            {{-- Exchange Ratio --}}
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Exchange Ratio</label>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">1:</span>
                    <input type="number" name="exchange_ratio" step="0.00000001" x-model="exchangeRatio" @input="calculateSettlementAmount" class="flex-1 px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="1.0000">
                </div>
            </div>

            {{-- Gross Exchange Amount --}}
            <div>
                <label class="block text-sm font-medium mb-2">Gross Exchange Amount</label>
                <input type="text" x-model="settlementAmountDisplay" @input="updateSettlementAmount()" @blur="formatSettlementAmountDisplay()" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00000">
                <input type="hidden" name="settlement_amount" x-model="settlementAmount">
            </div>
        </div>

        {{-- Card 7: Exchange Fees --}}
        <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Exchange Fees</h2>
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Fixed Fee</label>
                <input type="number" name="exchange_fixed_fee" step="0.00001" x-model="exchangeFixedFee" @input="calculateExchangeFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00000">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Percentage Fee (%)</label>
                <input type="number" name="exchange_percentage_fee" step="0.01" x-model="exchangePercentageFee" @input="calculateExchangeFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Minimum Fee</label>
                <input type="number" name="exchange_minimum_fee" step="0.00001" x-model="exchangeMinimumFee" @input="calculateExchangeFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00000">
            </div>

            <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                <label class="block text-sm font-medium mb-2">Total Fee</label>
                <input type="text" x-model="exchangeTotalFeeDisplay" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--card-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="0.00000">
                <input type="hidden" name="exchange_total_fee" x-model="exchangeTotalFee">
            </div>
        </div>

        {{-- Card 8: Net Exchange Amount --}}
        <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Net Exchange Amount</h2>
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Exchange Currency</label>
                <input type="text" x-model="settlementCurrencyCode" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="Select currency in Funds Exchanged">
            </div>

            <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                <label class="block text-sm font-medium mb-2">Net Exchange Amount</label>
                <input type="text" x-model="netExchangeAmountDisplay" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--card-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="0.00000">
                <p class="text-xs mt-2 opacity-70">Calculated as: Gross Exchange Amount - Exchange Total Fee</p>
            </div>
        </div>

            {{-- Card 9: Record Exchanged --}}
            <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                <div class="flex justify-center">
                    <button type="button" @click="setStatusExchanged()" :disabled="loadingExchanged" class="px-6 py-2 rounded-md font-medium hover:opacity-80 flex items-center gap-2" style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                        <svg x-show="loadingExchanged" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Record Exchanged</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Phase 3: Settling Funds --}}
        <div class="rounded-lg p-6 mb-6 transition-opacity" :class="!phase3Enabled && 'opacity-50 pointer-events-none'" style="background-color: rgba(147, 51, 234, 0.1); border: 2px solid #9333ea;">
            <h1 class="text-2xl font-bold mb-6" style="color: #ffffff;">Phase 3: Settling Funds</h1>
            
            {{-- Card 10: Outgoing Fees --}}
            <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Outgoing Fees</h2>
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Fixed Fee</label>
                <input type="number" name="outgoing_fixed_fee" step="0.00001" x-model="outgoingFixedFee" @input="calculateOutgoingFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00000">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Percentage Fee (%)</label>
                <input type="number" name="outgoing_percentage_fee" step="0.01" x-model="outgoingPercentageFee" @input="calculateOutgoingFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Minimum Fee</label>
                <input type="number" name="outgoing_minimum_fee" step="0.00001" x-model="outgoingMinimumFee" @input="calculateOutgoingFees" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="0.00000">
            </div>

            <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                <label class="block text-sm font-medium mb-2">Total Fee</label>
                <input type="text" x-model="outgoingTotalFeeDisplay" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--card-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="0.00000">
                <input type="hidden" name="outgoing_total_fee" x-model="outgoingTotalFee">
            </div>
        </div>

        {{-- Card 11: Final Destination Settlement Amount --}}
        <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Final Destination Settlement Amount</h2>
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Final Settlement Currency</label>
                <input type="text" x-model="settlementCurrencyCode" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="Select currency in Funds Exchanged">
                <input type="hidden" name="final_settlement_currency_code" x-model="settlementCurrencyCode">
            </div>

            <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                <label class="block text-sm font-medium mb-2">Final Settlement Amount</label>
                <input type="text" x-model="finalSettlementAmountDisplay" readonly class="w-full px-3 py-2 rounded-md" style="background-color: var(--card-background-color); border: 1px solid rgba(255,255,255,0.1); opacity: 0.7; cursor: not-allowed;" placeholder="0.00000">
                <input type="hidden" name="final_settlement_amount" x-model="finalSettlementAmount">
                <p class="text-xs mt-2 opacity-70">Calculated as: Destination/Settlement Amount - Outgoing Total Fee</p>
            </div>
        </div>

        {{-- Card 12: Destination / Settlement Account --}}
        <div class="rounded-lg p-6 mb-4" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">Destination / Settlement Account</h2>

            {{-- Crypto Destination --}}
            <div x-show="settlementType === 'crypto'">
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Network *</label>
                    <select name="crypto_network" x-bind:required="settlementType === 'crypto'" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        <option value="">Select Network</option>
                        <option value="solana">Solana</option>
                        <option value="ethereum">Ethereum</option>
                        <option value="polygon">Polygon</option>
                        <option value="bsc">BSC (Binance Smart Chain)</option>
                        <option value="avalanche">Avalanche</option>
                        <option value="arbitrum">Arbitrum</option>
                        <option value="optimism">Optimism</option>
                        <option value="base">Base</option>
                        <option value="tron">Tron</option>
                        <option value="stellar">Stellar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Destination / Settlement Wallet Address *</label>
                    <input type="text" name="crypto_wallet_address" x-bind:required="settlementType === 'crypto'" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="Enter wallet address">
                </div>
            </div>

            {{-- Fiat Destination --}}
            <div x-show="settlementType === 'fiat'">
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Destination Network *</label>
                    <select name="fiat_payment_method" x-bind:required="settlementType === 'fiat'" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                        <option value="">Select Network</option>
                        <option value="SEPA">SEPA</option>
                        <option value="SWIFT">SWIFT</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Account Holder Name *</label>
                    <input type="text" name="fiat_account_holder_name" x-bind:required="settlementType === 'fiat'" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="Full name on account">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Bank Account Number / IBAN *</label>
                    <input type="text" name="fiat_bank_account_number" x-bind:required="settlementType === 'fiat'" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="Account number or IBAN">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Routing / Sort Code</label>
                    <input type="text" name="fiat_bank_routing_number" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="Routing or sort code">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">SWIFT / BIC Code</label>
                    <input type="text" name="fiat_bank_swift_code" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="SWIFT/BIC code">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Bank Name</label>
                    <input type="text" name="fiat_bank_name" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="Bank name">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Country *</label>
                    <input type="text" name="fiat_bank_country" x-bind:required="settlementType === 'fiat'" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="Country">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Bank Address</label>
                    <textarea name="fiat_bank_address" rows="2" class="w-full px-3 py-2 rounded-md" style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);" placeholder="Bank address"></textarea>
                </div>
            </div>

            {{-- Card 13: Record Settled --}}
            <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                <div class="flex justify-center">
                    <button type="button" @click="setStatusSettled()" :disabled="loadingSettled" class="px-6 py-2 rounded-md font-medium hover:opacity-80 flex items-center gap-2" style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                        <svg x-show="loadingSettled" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Record Settled</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
