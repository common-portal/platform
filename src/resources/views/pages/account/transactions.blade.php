@extends('layouts.platform')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Transactions</h1>

    @if($transactions->isEmpty())
    <div class="rounded-lg p-8 text-center" style="background-color: var(--card-background-color);">
        <p class="opacity-70">No transactions recorded yet.</p>
    </div>
    @else
    <div class="rounded-lg overflow-hidden" style="background-color: var(--card-background-color);">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead style="background-color: var(--content-background-color);">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium">Updated</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Currency</th>
                        <th class="px-4 py-3 text-right text-sm font-medium">Amount</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                        <th class="px-4 py-3 text-center text-sm font-medium">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                    <tr x-data="{ expanded: false }" class="border-t" style="border-color: rgba(255,255,255,0.1);">
                        <td class="px-4 py-3 text-sm">
                            {{ $transaction->datetime_updated->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium">
                            {{ $transaction->currency_code }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-mono">
                            {{ number_format($transaction->amount, 5) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded text-xs font-medium" style="
                                @if($transaction->transaction_status === 'settled')
                                    background-color: var(--status-success-color); color: white;
                                @elseif($transaction->transaction_status === 'exchanged')
                                    background-color: var(--status-warning-color); color: #1a1a2e;
                                @else
                                    background-color: var(--status-info-color); color: white;
                                @endif
                            ">
                                {{ ucfirst($transaction->transaction_status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="expanded = !expanded" class="text-sm hover:opacity-70" style="color: var(--brand-primary-color);">
                                <span x-show="!expanded">▼ Show</span>
                                <span x-show="expanded">▲ Hide</span>
                            </button>
                        </td>
                    </tr>
                    <tr x-show="expanded" x-collapse class="border-t" style="border-color: rgba(255,255,255,0.1); background-color: var(--content-background-color);">
                        <td colspan="5" class="px-4 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                {{-- Left Column --}}
                                <div class="space-y-3">
                                    <div>
                                        <span class="opacity-70">Transaction ID:</span>
                                        <span class="font-mono ml-2">{{ $transaction->record_unique_identifier }}</span>
                                    </div>
                                    @if($transaction->exchange_ratio)
                                    <div>
                                        <span class="opacity-70">Exchange Ratio:</span>
                                        <span class="ml-2">1:{{ number_format($transaction->exchange_ratio, 8) }}</span>
                                    </div>
                                    @endif
                                    <div>
                                        <span class="opacity-70">Settlement Type:</span>
                                        <span class="ml-2 font-medium">{{ strtoupper($transaction->settlement_account_type) }}</span>
                                    </div>
                                    
                                    @if($transaction->settlement_account_type === 'crypto')
                                        <div>
                                            <span class="opacity-70">Network:</span>
                                            <span class="ml-2">{{ ucfirst($transaction->crypto_network) }}</span>
                                        </div>
                                        <div>
                                            <span class="opacity-70">Wallet Address:</span>
                                            <div class="mt-1 font-mono text-xs break-all p-2 rounded" style="background-color: var(--card-background-color);">
                                                {{ $transaction->crypto_wallet_address }}
                                            </div>
                                        </div>
                                    @endif

                                    @if($transaction->settlement_account_type === 'fiat')
                                        <div>
                                            <span class="opacity-70">Payment Method:</span>
                                            <span class="ml-2">{{ $transaction->fiat_payment_method }}</span>
                                        </div>
                                        <div>
                                            <span class="opacity-70">Account Holder:</span>
                                            <span class="ml-2">{{ $transaction->fiat_account_holder_name }}</span>
                                        </div>
                                        <div>
                                            <span class="opacity-70">Account Number:</span>
                                            <div class="mt-1 font-mono text-xs p-2 rounded" style="background-color: var(--card-background-color);">
                                                {{ $transaction->fiat_bank_account_number }}
                                            </div>
                                        </div>
                                        @if($transaction->fiat_bank_swift_code)
                                        <div>
                                            <span class="opacity-70">SWIFT/BIC:</span>
                                            <span class="ml-2">{{ $transaction->fiat_bank_swift_code }}</span>
                                        </div>
                                        @endif
                                        @if($transaction->fiat_bank_country)
                                        <div>
                                            <span class="opacity-70">Country:</span>
                                            <span class="ml-2">{{ $transaction->fiat_bank_country }}</span>
                                        </div>
                                        @endif
                                    @endif
                                </div>

                                {{-- Right Column: Timestamps --}}
                                <div class="space-y-3">
                                    <div class="font-medium mb-2">Timeline:</div>
                                    @if($transaction->datetime_received)
                                    <div>
                                        <span class="opacity-70">✓ Received:</span>
                                        <span class="ml-2">{{ $transaction->datetime_received->format('Y-m-d H:i:s') }}</span>
                                    </div>
                                    @endif
                                    @if($transaction->datetime_exchanged)
                                    <div>
                                        <span class="opacity-70">✓ Exchanged:</span>
                                        <span class="ml-2">{{ $transaction->datetime_exchanged->format('Y-m-d H:i:s') }}</span>
                                    </div>
                                    @endif
                                    @if($transaction->datetime_settled)
                                    <div>
                                        <span class="opacity-70">✓ Settled:</span>
                                        <span class="ml-2">{{ $transaction->datetime_settled->format('Y-m-d H:i:s') }}</span>
                                    </div>
                                    @endif
                                    <div class="pt-2 border-t" style="border-color: rgba(255,255,255,0.1);">
                                        <span class="opacity-70 text-xs">Created:</span>
                                        <span class="ml-2 text-xs">{{ $transaction->datetime_created->format('Y-m-d H:i:s') }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($transactions->hasPages())
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
