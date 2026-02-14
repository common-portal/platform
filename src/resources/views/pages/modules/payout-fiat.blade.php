@extends('layouts.platform')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>

<div class="max-w-2xl mx-auto" x-data="{
    selectedIbanId: '',
    selectedIban: null,
    amount: '',
    currency: 'EUR',
    rail: 'sepa',
    beneficiaryName: '',
    destinationIban: '',
    bicSwift: '',
    bankName: '',
    reference: '',
    submitting: false,
    showToast: false,
    toastMessage: '',
    toastType: 'success',

    ibans: @js($ibans->map(fn($i) => ['id' => $i->id, 'name' => $i->iban_friendly_name ?? 'IBAN', 'iban' => $i->iban_number ?? '', 'currency' => $i->iban_currency_iso3 ?? 'EUR'])),

    selectIban() {
        this.selectedIban = this.ibans.find(i => i.id == this.selectedIbanId) || null;
        if (this.selectedIban && this.selectedIban.currency) {
            this.currency = this.selectedIban.currency;
        }
    },

    async submitPayout() {
        if (!this.selectedIbanId || !this.amount || !this.beneficiaryName || !this.destinationIban) return;
        this.submitting = true;

        try {
            const response = await fetch('{{ route('modules.payout.fiat.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    source_iban_id: this.selectedIbanId,
                    amount: this.amount,
                    currency: this.currency,
                    rail: this.rail,
                    beneficiary_name: this.beneficiaryName,
                    destination_iban: this.destinationIban,
                    bic_swift: this.bicSwift || null,
                    bank_name: this.bankName || null,
                    reference: this.reference || null
                })
            });

            const data = await response.json();

            if (data.success) {
                this.toastType = 'success';
                this.toastMessage = data.message || 'Payout submitted successfully.';
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
        <h1 class="text-2xl font-bold">{{ __translator('Payout Fiat') }}</h1>
    </div>

    {{-- Payout Form --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">

        {{-- Step 1: Source IBAN --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2" style="color: var(--status-warning-color);">
                {{ __translator('1. Source IBAN') }}
            </label>
            <select x-model="selectedIbanId" @change="selectIban()"
                    class="w-full px-4 py-3 rounded-md text-sm"
                    style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                <option value="">{{ __translator('Select an IBAN...') }}</option>
                @foreach($ibans as $iban)
                <option value="{{ $iban->id }}">
                    {{ $iban->iban_friendly_name ?? 'IBAN' }} — {{ $iban->iban_number ?? '' }} ({{ $iban->iban_currency_iso3 ?? 'EUR' }})
                </option>
                @endforeach
            </select>
        </div>

        {{-- Step 2: Amount + Currency --}}
        <div class="mb-6" x-show="selectedIban" x-cloak>
            <label class="block text-sm font-semibold mb-2" style="color: var(--status-warning-color);">
                {{ __translator('2. Amount') }}
            </label>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input type="number" step="0.01" x-model="amount"
                           placeholder="0.00"
                           class="w-full px-4 py-3 rounded-md text-sm font-mono"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <select x-model="currency"
                        class="px-4 py-3 rounded-md text-sm font-semibold"
                        style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1); min-width: 90px;">
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                    <option value="USD">USD</option>
                </select>
            </div>
        </div>

        {{-- Step 3: Rail --}}
        <div class="mb-6" x-show="selectedIban" x-cloak>
            <label class="block text-sm font-semibold mb-2" style="color: var(--status-warning-color);">
                {{ __translator('3. Payout Rail') }}
            </label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" x-model="rail" value="sepa" class="sr-only peer">
                    <div class="px-4 py-3 rounded-md text-sm text-center font-medium transition-all peer-checked:ring-2"
                         :style="rail === 'sepa' ? 'background-color: var(--brand-primary-color); color: #1a1a2e; ring-color: var(--brand-primary-color);' : 'background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);'">
                        <div class="font-semibold">SEPA</div>
                        <div class="text-xs mt-1" :style="rail === 'sepa' ? 'opacity: 0.7;' : 'opacity: 0.5;'">{{ __translator('EUR zone, 1-2 days') }}</div>
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" x-model="rail" value="swift" class="sr-only peer">
                    <div class="px-4 py-3 rounded-md text-sm text-center font-medium transition-all peer-checked:ring-2"
                         :style="rail === 'swift' ? 'background-color: var(--brand-primary-color); color: #1a1a2e; ring-color: var(--brand-primary-color);' : 'background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);'">
                        <div class="font-semibold">SWIFT</div>
                        <div class="text-xs mt-1" :style="rail === 'swift' ? 'opacity: 0.7;' : 'opacity: 0.5;'">{{ __translator('International, 2-5 days') }}</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Step 4: Destination Account Details --}}
        <div class="mb-6" x-show="selectedIban" x-cloak>
            <label class="block text-sm font-semibold mb-2" style="color: var(--status-warning-color);">
                {{ __translator('4. Destination Account') }}
            </label>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs opacity-70 mb-1">{{ __translator('Beneficiary Name') }} *</label>
                    <input type="text" x-model="beneficiaryName"
                           placeholder="{{ __translator('e.g., John Smith') }}"
                           class="w-full px-4 py-3 rounded-md text-sm"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div>
                    <label class="block text-xs opacity-70 mb-1">{{ __translator('Destination IBAN / Account Number') }} *</label>
                    <input type="text" x-model="destinationIban"
                           placeholder="{{ __translator('e.g., DE89370400440532013000') }}"
                           class="w-full px-4 py-3 rounded-md text-sm font-mono"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs opacity-70 mb-1">{{ __translator('BIC / SWIFT Code') }}</label>
                        <input type="text" x-model="bicSwift"
                               placeholder="{{ __translator('e.g., COBADEFFXXX') }}"
                               class="w-full px-4 py-3 rounded-md text-sm font-mono"
                               style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    </div>
                    <div>
                        <label class="block text-xs opacity-70 mb-1">{{ __translator('Bank Name') }}</label>
                        <input type="text" x-model="bankName"
                               placeholder="{{ __translator('e.g., Deutsche Bank') }}"
                               class="w-full px-4 py-3 rounded-md text-sm"
                               style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                    </div>
                </div>
                <div>
                    <label class="block text-xs opacity-70 mb-1">{{ __translator('Payment Reference') }}</label>
                    <input type="text" x-model="reference"
                           placeholder="{{ __translator('e.g., Invoice #12345') }}"
                           class="w-full px-4 py-3 rounded-md text-sm"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div x-show="selectedIban" x-cloak>
            <button @click="submitPayout()"
                    :disabled="submitting || !amount || !beneficiaryName || !destinationIban"
                    class="w-full px-6 py-3 rounded-md font-semibold text-sm transition-opacity"
                    :class="submitting ? 'opacity-50 cursor-wait' : 'hover:opacity-80'"
                    style="background-color: var(--brand-primary-color); color: #1a1a2e;">
                <span x-show="!submitting">{{ __translator('Submit Payout') }}</span>
                <span x-show="submitting" x-cloak>{{ __translator('Processing...') }}</span>
            </button>
        </div>

        {{-- Empty State --}}
        @if($ibans->isEmpty())
        <div class="text-center py-8 opacity-70">
            <p>{{ __translator('No IBANs found. Please set up an IBAN first.') }}</p>
            <a href="{{ route('modules.ibans') }}" class="mt-3 inline-block text-sm underline" style="color: var(--brand-primary-color);">
                {{ __translator('Go to IBANs') }}
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
