<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Direct Debit Mandate Authorization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Direct Debit Mandate Authorization</h1>
            <p class="text-gray-600">Please confirm your payment details</p>
        </div>

        @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Customer Name:</span>
                    <span class="text-gray-900 font-semibold">{{ $customer->customer_full_name }}</span>
                </div>
                @if($customer->customer_primary_contact_name)
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Contact Person:</span>
                    <span class="text-gray-900">{{ $customer->customer_primary_contact_name }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Email:</span>
                    <span class="text-gray-900">{{ $customer->customer_primary_contact_email }}</span>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recurring Payment Details</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Payment Frequency:</span>
                    <span class="text-gray-900 font-semibold">
                        @if($customer->recurring_frequency === 'weekly')
                            Once per Week
                        @elseif($customer->recurring_frequency === 'twice_monthly')
                            Twice per Month
                        @elseif($customer->recurring_frequency === 'monthly')
                            Once per Month
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Payment Amount:</span>
                    <span class="text-gray-900 font-semibold text-lg">{{ number_format($customer->billing_amount, 2) }} {{ $customer->billing_currency }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Billing Date(s):</span>
                    <span class="text-gray-900">
                        @if($customer->recurring_frequency === 'weekly')
                            @php
                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                $dayIndex = $customer->billing_dates[0] ?? 0;
                            @endphp
                            {{ $days[$dayIndex] ?? 'Not specified' }}
                        @else
                            {{ implode(', ', array_map(fn($d) => 'Day ' . $d, $customer->billing_dates)) }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('public.mandate.confirm', $customer->record_unique_identifier) }}" class="space-y-6"
              x-data="{ ...bankLookup(), submitting: false }"
              @submit="submitting = true">
            @csrf

            {{-- Bank details — always editable, pre-populated if on file --}}
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Bank Account Details</h2>
                <p class="text-sm text-gray-600 mb-6">Please verify or enter your bank details to authorize recurring direct debit payments.</p>
                
                <div class="space-y-4">
                    {{-- 1. Name on Account --}}
                    <div>
                        <label for="billing_name_on_account" class="block text-sm font-medium text-gray-700 mb-2">
                            Name on Account
                        </label>
                        <input type="text" 
                               id="billing_name_on_account" 
                               name="billing_name_on_account" 
                               maxlength="255"
                               placeholder="e.g., ACME LLC"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                               value="{{ old('billing_name_on_account', $customer->billing_name_on_account) }}">
                    </div>

                    {{-- 2. BIC / SWIFT Code --}}
                    <div>
                        <label for="customer_bic" class="block text-sm font-medium text-gray-700 mb-2">
                            BIC / SWIFT Code *
                        </label>
                        <input type="text" 
                               id="customer_bic" 
                               name="customer_bic" 
                               required
                               maxlength="11"
                               placeholder="DEUTDEFF"
                               @blur="lookupFromBic($event.target.value)"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm uppercase"
                               value="{{ old('customer_bic', $customer->customer_bic) }}">
                        <p class="text-xs text-gray-500 mt-1">Bank name will be auto-detected from BIC or IBAN</p>
                    </div>

                    {{-- 3. IBAN --}}
                    <div>
                        <label for="customer_iban" class="block text-sm font-medium text-gray-700 mb-2">
                            IBAN (International Bank Account Number) *
                        </label>
                        <input type="text" 
                               id="customer_iban" 
                               name="customer_iban" 
                               required
                               maxlength="34"
                               placeholder="DE89370400440532013000"
                               @blur="validateAndLookupIban($event.target.value)"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm uppercase"
                               value="{{ old('customer_iban', $customer->customer_iban) }}">
                        <p x-show="ibanError" x-text="ibanError" class="text-xs mt-1 text-red-600"></p>
                        <p x-show="ibanValid" class="text-xs mt-1 text-green-600">IBAN checksum valid</p>
                        <p x-show="!ibanError && !ibanValid" class="text-xs text-gray-500 mt-1">Enter your IBAN without spaces</p>
                    </div>

                    {{-- 4. Bank Name (auto-populated) --}}
                    <div>
                        <label for="billing_bank_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Bank Name
                            <span x-show="isLoading" class="inline-flex items-center ml-2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-xs ml-1 text-gray-500">Identifying bank...</span>
                            </span>
                        </label>
                        <input type="text" 
                               id="billing_bank_name" 
                               name="billing_bank_name" 
                               x-model="bankName"
                               :readonly="isLoading"
                               maxlength="255"
                               placeholder="Auto-detected from BIC/IBAN or enter manually"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                               :class="isLoading ? 'bg-gray-100' : ''">
                        <p x-show="lookupError" x-text="lookupError" class="text-xs mt-1 text-red-600"></p>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm text-yellow-800 font-medium mb-1">Important Notice</p>
                        <p class="text-sm text-yellow-700">
                            By submitting this form, you authorize recurring direct debit payments as specified above. 
                            You can revoke this authorization at any time by contacting the merchant.
                        </p>
                    </div>
                </div>
            </div>

            <fieldset :disabled="submitting">
            <div class="flex justify-between items-center pt-4">
                <a href="{{ url('/support') }}?customer={{ $customer->record_unique_identifier }}" 
                   class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors text-center no-underline"
                   :class="submitting ? 'opacity-50 pointer-events-none' : ''">
                    Contact Support
                </a>
                <button type="submit" 
                        :disabled="submitting"
                        class="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 transition-colors inline-flex items-center gap-2"
                        :class="submitting ? 'opacity-75 cursor-not-allowed' : ''">
                    <svg x-show="submitting" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="submitting ? 'Processing...' : 'Confirm Mandate Authorization'"></span>
                </button>
            </div>
            </fieldset>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
            <p>Your information is transmitted securely and will be used only for processing your direct debit mandate.</p>
        </div>

    </div>

<script>
function bankLookup() {
    return {
        bankName: '{{ old("billing_bank_name", $customer->billing_bank_name ?? "") }}',
        isLoading: false,
        lookupError: '',
        ibanError: '',
        ibanValid: false,
        _validateIbanChecksum(iban) {
            iban = iban.replace(/\s+/g, '').toUpperCase();
            if (iban.length < 15 || iban.length > 34) return false;
            if (!/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/.test(iban)) return false;
            const rearranged = iban.slice(4) + iban.slice(0, 4);
            let numStr = '';
            for (let i = 0; i < rearranged.length; i++) {
                const ch = rearranged[i];
                if (ch >= 'A' && ch <= 'Z') {
                    numStr += (ch.charCodeAt(0) - 55).toString();
                } else {
                    numStr += ch;
                }
            }
            let remainder = 0;
            for (let i = 0; i < numStr.length; i++) {
                remainder = (remainder * 10 + parseInt(numStr[i])) % 97;
            }
            return remainder === 1;
        },
        async validateAndLookupIban(iban) {
            iban = (iban || '').trim().toUpperCase();
            this.ibanError = '';
            this.ibanValid = false;

            if (!iban) return;
            if (iban.length < 15) {
                this.ibanError = 'IBAN is too short';
                return;
            }

            if (!this._validateIbanChecksum(iban)) {
                this.ibanError = 'Invalid IBAN — checksum failed';
                return;
            }

            this.ibanValid = true;

            if (this.bankName) return;

            await this._doLookup({ iban: iban });
        },
        async lookupFromBic(bic) {
            bic = (bic || '').trim().toUpperCase();
            this.lookupError = '';

            if (this.bankName) return;
            if (!bic || bic.length < 8) return;

            await this._doLookup({ bic: bic });
        },
        async _doLookup(payload) {
            this.isLoading = true;

            try {
                const response = await fetch('/public/lookup-bank', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (data.success && data.bank_name) {
                    this.bankName = data.bank_name;
                } else {
                    this.lookupError = data.message || 'Could not identify bank';
                }
            } catch (error) {
                this.lookupError = 'Network error - please enter bank name manually';
            } finally {
                this.isLoading = false;
            }
        }
    };
}
</script>

</body>
</html>
