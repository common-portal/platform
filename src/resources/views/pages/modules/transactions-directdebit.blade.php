@extends('layouts.platform')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>

<div class="max-w-4xl mx-auto" x-data="{
    showToast: false,
    toastMessage: '',
    toastType: 'success'
}" @copy-value.window="toastMessage = $event.detail; toastType = 'success'; showToast = true; setTimeout(() => showToast = false, 3000)"
   @refund-success.window="toastMessage = $event.detail; toastType = 'success'; showToast = true; setTimeout(() => showToast = false, 4000)"
   @refund-error.window="toastMessage = $event.detail; toastType = 'error'; showToast = true; setTimeout(() => showToast = false, 5000)">

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
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="font-medium text-sm" x-text="toastMessage"></span>
        </div>
    </div>

    <h1 class="text-2xl font-bold mb-6">{{ __translator('Direct Debit Transactions') }}</h1>

    {{-- Search / Filter Card --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: #333333;">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-warning-color);">{{ __translator('Search Collections') }}</h2>

        <form method="GET" action="{{ route('modules.transactions.directdebit') }}">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Customer Name') }}</label>
                    <input type="text" name="customer_name" value="{{ request('customer_name') }}"
                           placeholder="{{ __translator('e.g., Sage Smith') }}"
                           class="w-full px-3 py-2 rounded-md text-sm"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Reference') }}</label>
                    <input type="text" name="reference" value="{{ request('reference') }}"
                           placeholder="{{ __translator('e.g., DD-SAGESMITH-20260209') }}"
                           class="w-full px-3 py-2 rounded-md text-sm font-mono"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Billing Date (From)') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full px-3 py-2 rounded-md text-sm"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Billing Date (To)') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full px-3 py-2 rounded-md text-sm"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __translator('Min Amount') }}</label>
                    <input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}"
                           placeholder="0.00"
                           class="w-full px-3 py-2 rounded-md text-sm"
                           style="background-color: var(--content-background-color); border: 1px solid rgba(255,255,255,0.1);">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Status') }}</label>
                <div class="flex gap-4 flex-wrap">
                    @foreach(['pending' => 'Pending', 'submitted' => 'Submitted', 'cleared' => 'Cleared', 'failed' => 'Failed', 'rejected' => 'Rejected', 'refunded' => 'Refunded'] as $val => $lbl)
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" name="status[]" value="{{ $val }}"
                                   {{ in_array($val, (array) request('status', [])) ? 'checked' : '' }}
                                   style="accent-color: var(--brand-secondary-color);">
                            {{ __translator($lbl) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 rounded-md text-sm font-semibold"
                        style="background-color: var(--brand-secondary-color); color: #1a1a2e;">
                    {{ __translator('Search') }}
                </button>
                <a href="{{ route('modules.transactions.directdebit') }}" class="px-5 py-2 rounded-md text-sm font-semibold"
                   style="background-color: rgba(255,255,255,0.1); color: #fff; text-decoration: none;">
                    {{ __translator('Clear Filters') }}
                </a>
            </div>
        </form>
    </div>

    {{-- Results --}}
    <div class="rounded-lg overflow-hidden" style="background-color: #333333;">
        <table class="w-full text-sm text-left">
            <thead>
                <tr style="background-color: rgba(255,255,255,0.05);">
                    <th class="py-3 px-3" style="width: 40px;"></th>
                    <th class="py-3 px-4 font-semibold text-xs uppercase tracking-wider" style="color: #9ca3af;">{{ __translator('Customer') }}</th>
                    <th class="py-3 px-4 font-semibold text-xs uppercase tracking-wider" style="color: #9ca3af;">{{ __translator('Amount') }}</th>
                    <th class="py-3 px-4 font-semibold text-xs uppercase tracking-wider" style="color: #9ca3af;">{{ __translator('Status') }}</th>
                    <th class="py-3 px-4 font-semibold text-xs uppercase tracking-wider" style="color: #9ca3af;">{{ __translator('Billing Date') }}</th>
                </tr>
            </thead>
            @forelse($collections as $collection)
                @php
                    $statusColors = [
                        'pending'   => '#f59e0b',
                        'submitted' => '#3b82f6',
                        'cleared'   => '#16a34a',
                        'failed'    => '#ef4444',
                        'rejected'  => '#dc2626',
                        'refunded'  => '#8b5cf6',
                    ];
                    $statusLabels = [
                        'pending'   => __translator('Pending'),
                        'submitted' => __translator('Submitted'),
                        'cleared'   => __translator('Cleared'),
                        'failed'    => __translator('Failed'),
                        'rejected'  => __translator('Rejected'),
                        'refunded'  => __translator('Refunded'),
                    ];
                    $sColor = $statusColors[$collection->status] ?? '#9ca3af';
                    $sLabel = $statusLabels[$collection->status] ?? ucfirst($collection->status);
                @endphp
                <tbody x-data="{ expanded: false, showRefundConfirm: false }">
                    <tr @click="expanded = !expanded"
                        class="border-b cursor-pointer transition-colors"
                        style="border-color: var(--sidebar-hover-background-color);"
                        :style="expanded ? 'background-color: rgba(255,255,255,0.03);' : ''"
                        @mouseenter="if(!expanded) $el.style.backgroundColor='rgba(255,255,255,0.05)'"
                        @mouseleave="$el.style.backgroundColor = expanded ? 'rgba(255,255,255,0.03)' : ''">
                        <td class="py-3 px-3" style="width: 40px; min-width: 40px;">
                            <span :style="expanded ? 'display:inline-block; transform: rotate(90deg); transition: transform 0.2s ease;' : 'display:inline-block; transform: rotate(0deg); transition: transform 0.2s ease;'" style="font-size: 22px; color: #f59e0b; line-height: 1;">&#9658;</span>
                        </td>
                        <td class="py-3 px-4 font-medium">{{ $collection->customer->customer_full_name ?? '—' }}</td>
                        <td class="py-3 px-4 font-mono font-semibold">{{ number_format($collection->amount, 2) }} {{ $collection->currency }}</td>
                        <td class="py-3 px-4">
                            <span style="color: {{ $sColor }}; font-weight: 500; font-size: 12px; background-color: {{ $sColor }}20; padding: 3px 10px; border-radius: 9999px; display: inline-block;">
                                {{ $sLabel }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span style="font-size: 12px; opacity: 0.6;">{{ $collection->billing_date->format('M d, Y') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="padding: 0;">
                            <div x-ref="details" :style="expanded ? 'max-height: ' + $refs.details.scrollHeight + 'px; opacity: 1; overflow: hidden; transition: max-height 0.3s ease, opacity 0.2s ease; background-color: #eef0f4; border-radius: 0 0 8px 8px;' : 'max-height: 0; opacity: 0; overflow: hidden; transition: max-height 0.3s ease, opacity 0.2s ease;'">
                            <div style="padding: 20px 24px 24px 46px; background-color: #eef0f4; color: #1f2937;">

                                {{-- Action Row --}}
                                <div style="display: flex; align-items: center; margin-bottom: 16px; gap: 12px;">
                                    <span style="color: {{ $sColor }}; font-weight: 600; font-size: 12px; padding: 6px 16px; border-radius: 6px; border: 1px solid {{ $sColor }}50; background-color: {{ $sColor }}15; display: inline-block;">
                                        {{ $sLabel }}
                                    </span>

                                    @if($collection->status === 'cleared')
                                        <button type="button" @click.stop="showRefundConfirm = true"
                                            style="background-color: #dc2626; color: #fff; font-size: 12px; font-weight: 600; padding: 6px 16px; border-radius: 6px; border: none; cursor: pointer; margin-left: auto;">
                                            &#8634; {{ __translator('Refund') }}
                                        </button>
                                    @endif
                                </div>

                                {{-- Section 1: Transaction Details --}}
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 8px;">{{ __translator('Transaction Details') }}</div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Customer Name') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $collection->customer->customer_full_name ?? '—' }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Amount') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ number_format($collection->amount, 2) }} {{ $collection->currency }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Billing Date') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $collection->billing_date->format('M d, Y') }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Reference') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937; font-family: monospace; cursor: pointer;"
                                              @click.stop="navigator.clipboard.writeText('{{ $collection->reference }}'); $dispatch('copy-value', '{{ $collection->reference }}')"
                                              title="{{ __translator('Click to copy') }}">
                                            {{ $collection->reference }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Sequence Type') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $collection->sequence_type === 'FRST' ? __translator('First Collection') : __translator('Recurring') }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Status') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: {{ $sColor }};">{{ $sLabel }}</span>
                                    </div>
                                </div>

                                {{-- Section 2: Banking Details --}}
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 8px;">{{ __translator('Banking Details') }}</div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Debtor IBAN') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937; font-family: monospace; cursor: pointer;"
                                              @click.stop="navigator.clipboard.writeText('{{ $collection->source_iban }}'); $dispatch('copy-value', '{{ $collection->source_iban }}')"
                                              title="{{ __translator('Click to copy') }}">
                                            {{ $collection->source_iban ?: '—' }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Creditor IBAN') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937; font-family: monospace; cursor: pointer;"
                                              @click.stop="navigator.clipboard.writeText('{{ $collection->destination_iban }}'); $dispatch('copy-value', '{{ $collection->destination_iban }}')"
                                              title="{{ __translator('Click to copy') }}">
                                            {{ $collection->destination_iban ?: '—' }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Amount (Minor Units)') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $collection->amount_minor_units }} {{ __translator('cents') }}</span>
                                    </div>
                                </div>

                                {{-- Section 3: SH Financial Details --}}
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 8px;">{{ __translator('SH Financial') }}</div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Transaction UID') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937; font-family: monospace; cursor: pointer;"
                                              @if($collection->sh_transaction_uid)
                                              @click.stop="navigator.clipboard.writeText('{{ $collection->sh_transaction_uid }}'); $dispatch('copy-value', '{{ $collection->sh_transaction_uid }}')"
                                              title="{{ __translator('Click to copy') }}"
                                              @endif>
                                            {{ $collection->sh_transaction_uid ?: '—' }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Correlation ID') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937; font-family: monospace; cursor: pointer;"
                                              @click.stop="navigator.clipboard.writeText('{{ $collection->correlation_id }}'); $dispatch('copy-value', '{{ $collection->correlation_id }}')"
                                              title="{{ __translator('Click to copy') }}">
                                            {{ $collection->correlation_id }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Batch ID') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $collection->sh_batch_id ?: '—' }}</span>
                                    </div>
                                </div>

                                {{-- Section 4: Timestamps --}}
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 8px;">{{ __translator('Timeline') }}</div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Created') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $collection->created_at_timestamp ? $collection->created_at_timestamp->format('M d, Y H:i:s') : '—' }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Submitted') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $collection->submitted_at ? $collection->submitted_at->format('M d, Y H:i:s') : '—' }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Cleared') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: {{ $collection->cleared_at ? '#16a34a' : '#1f2937' }};">{{ $collection->cleared_at ? $collection->cleared_at->format('M d, Y H:i:s') : '—' }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Failed / Rejected') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: {{ $collection->failed_at ? '#ef4444' : '#1f2937' }};">{{ $collection->failed_at ? $collection->failed_at->format('M d, Y H:i:s') : '—' }}</span>
                                    </div>
                                </div>

                                {{-- Section 5: Failure Reason (if any) --}}
                                @if($collection->failure_reason)
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #ef4444; margin-bottom: 8px;">{{ __translator('Failure Details') }}</div>
                                    <div style="padding: 10px 14px; background-color: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 6px;">
                                        <span style="font-size: 13px; color: #dc2626;">{{ $collection->failure_reason }}</span>
                                    </div>
                                    @if($collection->retry_count > 0)
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; margin-top: 4px;">
                                        <span style="font-size: 13px; color: #6b7280;">{{ __translator('Retry Count') }}</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #ef4444;">{{ $collection->retry_count }}</span>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                {{-- Refund Confirmation Modal --}}
                                @if($collection->status === 'cleared')
                                <div x-show="showRefundConfirm" x-cloak @click.away="showRefundConfirm = false"
                                    style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.5);">
                                    <div @click.stop style="background-color: #fff; border-radius: 12px; padding: 24px; max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                                        <h3 style="font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">{{ __translator('Refund Collection') }}</h3>
                                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 8px;">
                                            {{ __translator('Are you sure you want to refund this direct debit collection?') }}
                                        </p>
                                        <div style="background-color: #f9fafb; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <span style="font-size: 12px; color: #6b7280;">{{ __translator('Customer') }}</span>
                                                <span style="font-size: 12px; font-weight: 600; color: #1f2937;">{{ $collection->customer->customer_full_name ?? '—' }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <span style="font-size: 12px; color: #6b7280;">{{ __translator('Amount') }}</span>
                                                <span style="font-size: 12px; font-weight: 600; color: #1f2937;">{{ number_format($collection->amount, 2) }} {{ $collection->currency }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="font-size: 12px; color: #6b7280;">{{ __translator('Reference') }}</span>
                                                <span style="font-size: 12px; font-weight: 600; color: #1f2937; font-family: monospace;">{{ $collection->reference }}</span>
                                            </div>
                                        </div>
                                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                            <button type="button" @click="showRefundConfirm = false"
                                                style="background-color: #e5e7eb; color: #374151; font-size: 12px; font-weight: 600; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">
                                                {{ __translator('Cancel') }}
                                            </button>
                                            <button type="button" @click.stop="
                                                fetch('{{ route('modules.transactions.directdebit.refund', $collection->id) }}', {
                                                    method: 'POST',
                                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                                }).then(r => r.json()).then(data => {
                                                    if (data.success) {
                                                        $dispatch('refund-success', data.message || '{{ __translator('Refund initiated successfully') }}');
                                                        setTimeout(() => location.reload(), 1500);
                                                    } else {
                                                        $dispatch('refund-error', data.message || '{{ __translator('Refund failed') }}');
                                                    }
                                                }).catch(() => {
                                                    $dispatch('refund-error', '{{ __translator('Network error — please try again') }}');
                                                });
                                                showRefundConfirm = false;
                                            "
                                                style="background-color: #dc2626; color: #fff; font-size: 12px; font-weight: 600; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">
                                                &#8634; {{ __translator('Confirm Refund') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endif

                            </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td colspan="5" class="py-12 text-center" style="color: #6b7280;">
                            <div style="font-size: 14px; margin-bottom: 4px;">{{ __translator('No direct debit collections found.') }}</div>
                            <div style="font-size: 12px; opacity: 0.7;">{{ __translator('Collections will appear here once the daily cron processes active mandates.') }}</div>
                        </td>
                    </tr>
                </tbody>
            @endforelse
        </table>
    </div>

    {{-- Pagination --}}
    @if($collections->hasPages())
    <div class="mt-6">
        {{ $collections->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
