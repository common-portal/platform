@extends('layouts.platform')

@section('content')
{{-- Customers Page --}}

<div class="max-w-6xl mx-auto" x-data="{ activeTab: 'existing', editingCustomer: null }">
    <h1 class="text-2xl font-bold mb-6">{{ __translator('Customers') }}</h1>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="flex mb-6 rounded-lg overflow-hidden" style="background-color: var(--sidebar-hover-background-color); border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
        <button @click="activeTab = 'existing'" 
                :class="activeTab === 'existing' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-right: 1px solid rgba(255,255,255,0.15); border-bottom: 2px solid ' + (activeTab === 'existing' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'existing' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                {{ __translator('Existing Customers') }}
            </span>
        </button>
        <button @click="activeTab = 'add'" 
                :class="activeTab === 'add' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-bottom: 2px solid ' + (activeTab === 'add' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'add' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                {{ __translator('Add / Edit Customer') }}
            </span>
        </button>
    </div>

    {{-- Existing Customers Tab --}}
    <div x-show="activeTab === 'existing'" x-cloak>
        <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">{{ __translator('Customer List') }}</h2>
                <div class="flex gap-2">
                    <input type="text" 
                           placeholder="{{ __translator('Search customers...') }}"
                           class="px-3 py-2 rounded-md text-sm border-0"
                           style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                    <button class="px-4 py-2 rounded-md text-sm font-medium"
                            style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                        {{ __translator('Search') }}
                    </button>
                </div>
            </div>

            <p class="text-sm opacity-70 mb-6">
                {{ __translator('Manage your customer database. View, edit, and organize customer information.') }}
            </p>

            {{-- Customer List --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                            <th class="py-3 px-4" style="width: 28px;"></th>
                            <th class="text-left py-3 px-4 font-medium opacity-70">{{ __translator('Name') }}</th>
                            <th class="text-left py-3 px-4 font-medium opacity-70">{{ __translator('Email') }}</th>
                            <th class="text-left py-3 px-4 font-medium opacity-70">{{ __translator('Status') }}</th>
                            <th class="text-left py-3 px-4 font-medium opacity-70">{{ __translator('Date') }}</th>
                        </tr>
                    </thead>
                        @forelse($customers as $customer)
                        @php
                            $statusColors = [
                                'invitation_pending' => '#f59e0b',
                                'mandate_confirmed' => '#10b981',
                                'mandate_active' => '#10b981',
                                'mandate_rejected' => '#ef4444',
                                'mandate_expired' => '#6b7280',
                            ];
                            $statusLabels = [
                                'invitation_pending' => __translator('Pending'),
                                'mandate_confirmed' => __translator('Authorized'),
                                'mandate_active' => __translator('Active'),
                                'mandate_rejected' => __translator('Rejected'),
                                'mandate_expired' => __translator('Expired'),
                            ];
                            $color = $statusColors[$customer->mandate_status] ?? '#6b7280';
                            $label = $statusLabels[$customer->mandate_status] ?? ucfirst(str_replace('_', ' ', $customer->mandate_status));

                            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            $billingSchedule = '';
                            if ($customer->recurring_frequency === 'daily') {
                                $billingSchedule = $customer->billing_start_date ? 'Starting ' . $customer->billing_start_date->format('M d, Y') : 'Every day';
                            } elseif ($customer->recurring_frequency === 'weekly') {
                                $selectedDays = collect($customer->billing_dates ?? [])
                                    ->filter(fn($d) => is_numeric($d))
                                    ->map(fn($d) => $dayNames[(int)$d] ?? null)
                                    ->filter()->values();
                                $billingSchedule = $selectedDays->isNotEmpty() ? $selectedDays->join(', ') : '—';
                            } elseif ($customer->recurring_frequency === 'monthly') {
                                $dates = collect($customer->billing_dates ?? [])
                                    ->filter(fn($d) => is_numeric($d))
                                    ->map(fn($d) => 'Day ' . $d)
                                    ->values();
                                $billingSchedule = $dates->isNotEmpty() ? $dates->join(', ') : '—';
                            }

                            $frequencyLabels = [
                                'daily' => __translator('Daily'),
                                'weekly' => __translator('Weekly'),
                                'monthly' => __translator('Monthly'),
                            ];
                        @endphp
                        <tbody x-data="{ expanded: false, isActive: {{ in_array($customer->mandate_status, ['mandate_confirmed', 'mandate_active']) && ($customer->mandate_active_or_paused ?? 'active') === 'active' ? 'true' : 'false' }} }" x-init="@if(session('expanded_customer_id') == $customer->id) $nextTick(() => { expanded = true; }) @endif">
                            <tr @click="expanded = !expanded" 
                                class="border-b cursor-pointer transition-colors"
                                style="border-color: var(--sidebar-hover-background-color);"
                                :style="expanded ? 'background-color: rgba(255,255,255,0.03);' : ''"
                                @mouseenter="if(!expanded) $el.style.backgroundColor='rgba(255,255,255,0.05)'"
                                @mouseleave="$el.style.backgroundColor = expanded ? 'rgba(255,255,255,0.03)' : ''">
                                <td class="py-3 px-3" style="width: 40px; min-width: 40px;">
                                    <span :style="expanded ? 'display:inline-block; transform: rotate(90deg); transition: transform 0.2s ease;' : 'display:inline-block; transform: rotate(0deg); transition: transform 0.2s ease;'" style="font-size: 22px; color: #f59e0b; line-height: 1;">&#9658;</span>
                                </td>
                                <td class="py-3 px-4 font-medium">{{ $customer->customer_full_name }}</td>
                                <td class="py-3 px-4">{{ $customer->customer_primary_contact_email }}</td>
                                <td class="py-3 px-4">
                                    <span style="color: {{ $color }}; font-weight: 500; font-size: 12px; background-color: {{ $color }}20; padding: 3px 10px; border-radius: 9999px; display: inline-block;">
                                        {{ $label }}
                                    </span>
                                    @if(in_array($customer->mandate_status, ['mandate_confirmed', 'mandate_active']))
                                        <br>
                                        <span x-show="isActive" style="color: #16a34a; font-weight: 500; font-size: 11px; background-color: rgba(34,197,94,0.15); padding: 2px 8px; border-radius: 9999px; display: inline-block; margin-top: 4px;">
                                            Active
                                        </span>
                                        <span x-show="!isActive" x-cloak style="color: #dc2626; font-weight: 500; font-size: 11px; background-color: rgba(239,68,68,0.15); padding: 2px 8px; border-radius: 9999px; display: inline-block; margin-top: 4px;">
                                            Paused
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span style="font-size: 12px; opacity: 0.6;">{{ $customer->created_at_timestamp->format('M d, Y') }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5" style="padding: 0;">
                                    <div x-ref="details" :style="expanded ? 'max-height: ' + $refs.details.scrollHeight + 'px; opacity: 1; overflow: hidden; transition: max-height 0.3s ease, opacity 0.2s ease; background-color: #eef0f4; border-radius: 0 0 8px 8px;' : 'max-height: 0; opacity: 0; overflow: hidden; transition: max-height 0.3s ease, opacity 0.2s ease;'">
                                    <div style="padding: 20px 24px 24px 46px; background-color: #eef0f4; color: #1f2937;">

                                        {{-- Action Buttons Row --}}
                                        <div x-data="{ showResendConfirm: false, showToggleConfirm: false }" style="display: flex; align-items: center; margin-bottom: 16px; gap: 12px;">
                                            {{-- Resend Mandate / Mandate Authorized (left) --}}
                                            <div style="position: relative;">
                                                @if($customer->mandate_status === 'invitation_pending')
                                                    <button type="button" @click.stop="showResendConfirm = true"
                                                        style="background-color: #f59e0b; color: #1a1a2e; font-size: 12px; font-weight: 600; padding: 6px 16px; border-radius: 6px; border: none; cursor: pointer;">
                                                        &#9993; {{ __translator('Resend Mandate') }}
                                                    </button>
                                                @else
                                                    <span title="{{ __translator('This mandate has already been authorized') }}"
                                                        style="background-color: rgba(34,197,94,0.1); color: #16a34a; font-size: 12px; font-weight: 600; padding: 6px 16px; border-radius: 6px; border: 1px solid rgba(34,197,94,0.35); cursor: default; display: inline-block;">
                                                        &#10003; {{ __translator('Mandate Authorized') }}
                                                    </span>
                                                @endif

                                                {{-- Resend Confirmation Modal --}}
                                                <div x-show="showResendConfirm" x-cloak @click.away="showResendConfirm = false"
                                                    style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.5);">
                                                    <div @click.stop style="background-color: #fff; border-radius: 12px; padding: 24px; max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                                                        <h3 style="font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">{{ __translator('Resend Mandate Authorization') }}</h3>
                                                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">
                                                            {{ __translator('This will resend the mandate authorization email to') }}
                                                            <strong>{{ $customer->customer_primary_contact_email }}</strong>.
                                                            {{ __translator('Are you sure?') }}
                                                        </p>
                                                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                                            <button type="button" @click="showResendConfirm = false"
                                                                style="background-color: #e5e7eb; color: #374151; font-size: 12px; font-weight: 600; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">
                                                                {{ __translator('Cancel') }}
                                                            </button>
                                                            <form method="POST" action="{{ route('account.customers.resend-mandate', $customer->id) }}" style="display: inline;">
                                                                @csrf
                                                                <button type="submit"
                                                                    style="background-color: #f59e0b; color: #1a1a2e; font-size: 12px; font-weight: 600; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">
                                                                    &#9993; {{ __translator('Confirm & Resend') }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Active/Paused Toggle Switch (center) --}}
                                            @if(in_array($customer->mandate_status, ['mandate_confirmed', 'mandate_active']))
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <button type="button" @click.stop="showToggleConfirm = true"
                                                        :title="isActive ? '{{ __translator('Click to pause collections') }}' : '{{ __translator('Click to activate collections') }}'"
                                                        :style="'position: relative; width: 40px; height: 22px; border-radius: 11px; border: none; cursor: pointer; transition: background-color 0.2s ease; flex-shrink: 0; background-color: ' + (isActive ? '#16a34a' : '#d1d5db')">
                                                        <span :style="'position: absolute; top: 2px; ' + (isActive ? 'left: 20px;' : 'left: 2px;') + ' width: 18px; height: 18px; background-color: #fff; border-radius: 50%; transition: left 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.2);'"></span>
                                                    </button>
                                                    <span :style="'font-size: 12px; font-weight: 600; color: ' + (isActive ? '#16a34a' : '#dc2626')"
                                                        x-text="isActive ? '{{ __translator('Direct debit collection active') }}' : '{{ __translator('Direct debit collection paused') }}'">
                                                    </span>
                                                </div>
                                            @elseif($customer->mandate_status === 'invitation_pending')
                                                <div style="display: flex; align-items: center; gap: 8px;" title="{{ __translator('Collections will activate automatically once the customer authorizes the mandate.') }}">
                                                    <span style="font-size: 11px; font-weight: 600; color: #9ca3af;">
                                                        {{ __translator('Paused') }}
                                                    </span>
                                                    <span style="position: relative; width: 40px; height: 22px; border-radius: 11px; display: inline-block; cursor: not-allowed; background-color: #e5e7eb;">
                                                        <span style="position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background-color: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.15);"></span>
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- Edit Button (right) --}}
                                            <button type="button" @click.stop="
                                                editingCustomer = @js($customer->toArray());
                                                activeTab = 'add';
                                                expanded = false;
                                            " style="background-color: #f59e0b; color: #1a1a2e; font-size: 12px; font-weight: 600; padding: 6px 16px; border-radius: 6px; border: none; cursor: pointer; margin-left: auto;">
                                                &#9998; {{ __translator('Edit') }}
                                            </button>

                                            {{-- Toggle Confirmation Modal --}}
                                            @if(in_array($customer->mandate_status, ['mandate_confirmed', 'mandate_active']))
                                            <div x-show="showToggleConfirm" x-cloak @click.away="showToggleConfirm = false"
                                                style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.5);">
                                                <div @click.stop style="background-color: #fff; border-radius: 12px; padding: 24px; max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                                                    <h3 style="font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 8px;"
                                                        x-text="isActive ? '{{ __translator('Pause Mandate') }}' : '{{ __translator('Activate Mandate') }}'"></h3>
                                                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">
                                                        <span x-show="isActive">{{ __translator('Are you sure you want to pause direct debit collections for') }} <strong>{{ $customer->customer_full_name }}</strong>? {{ __translator('No debits will be collected until the mandate is reactivated.') }}</span>
                                                        <span x-show="!isActive" x-cloak>{{ __translator('Are you sure you want to reactivate direct debit collections for') }} <strong>{{ $customer->customer_full_name }}</strong>? {{ __translator('Debits will resume according to the billing schedule.') }}</span>
                                                    </p>
                                                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                                        <button type="button" @click="showToggleConfirm = false"
                                                            style="background-color: #e5e7eb; color: #374151; font-size: 12px; font-weight: 600; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">
                                                            {{ __translator('Cancel') }}
                                                        </button>
                                                        <button type="button" @click.stop="
                                                            fetch('{{ route('account.customers.toggle-mandate-status', $customer->id) }}', {
                                                                method: 'POST',
                                                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                                                                body: 'mandate_active_or_paused=' + (isActive ? 'paused' : 'active')
                                                            }).then(() => { isActive = !isActive; });
                                                            showToggleConfirm = false;
                                                        "
                                                            :style="'font-size: 12px; font-weight: 600; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; ' + (isActive ? 'background-color: #dc2626; color: #fff;' : 'background-color: #16a34a; color: #fff;')"
                                                            x-text="isActive ? '{{ __translator('Confirm Pause') }}' : '{{ __translator('Confirm Activate') }}'">
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>

                                        {{-- Section 1: Customer Details --}}
                                        <div style="margin-bottom: 16px;">
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 8px;">{{ __translator('Customer Details') }}</div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Customer ID') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937; font-family: monospace; cursor: pointer;"
                                                      @click.stop="navigator.clipboard.writeText('{{ $customer->record_unique_identifier }}'); $dispatch('copy-value', '{{ __translator('Customer ID copied') }}')"
                                                      title="{{ __translator('Click to copy') }}">
                                                    {{ $customer->record_unique_identifier }}
                                                </span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Customer Name') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->customer_full_name }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Contact Name') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->customer_primary_contact_name ?: '—' }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Email') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->customer_primary_contact_email }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Status') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: {{ $color }};">{{ $label }}</span>
                                            </div>
                                        </div>

                                        {{-- Section 2: Billing Schedule --}}
                                        <div style="margin-bottom: 16px;">
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 8px;">{{ __translator('Billing Schedule') }}</div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Frequency') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $frequencyLabels[$customer->recurring_frequency] ?? ucfirst($customer->recurring_frequency) }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Billing Schedule') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $billingSchedule }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Amount') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ number_format($customer->billing_amount, 2) }} {{ $customer->billing_currency }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Invitation Sent') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->invitation_sent_at ? $customer->invitation_sent_at->format('M d, Y H:i') : '—' }}</span>
                                            </div>
                                        </div>

                                        {{-- Section 3: Billing Details --}}
                                        <div style="margin-bottom: 16px;">
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 8px;">{{ __translator('Billing Details') }}</div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('IBAN') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->customer_iban ?: '—' }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('BIC') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->customer_bic ?: '—' }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Bank Name') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->billing_bank_name ?: '—' }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Name on Account') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->billing_name_on_account ?: '—' }}</span>
                                            </div>
                                        </div>

                                        {{-- Section 4: Settlement Account --}}
                                        <div>
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 8px;">{{ __translator('Settlement Account') }}</div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.08);">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Settlement Account IBAN') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->settlementIban?->iban_number ?: '—' }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                                <span style="font-size: 13px; color: #6b7280;">{{ __translator('Settlement Account Name') }}</span>
                                                <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $customer->settlementIban?->iban_friendly_name ?: '—' }}</span>
                                            </div>
                                        </div>

                                    </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        @empty
                        <tbody>
                            <tr>
                                <td colspan="5" class="py-8 text-center opacity-70">
                                    {{ __translator('No customers found. Add your first customer using the "Add Customer" tab.') }}
                                </td>
                            </tr>
                        </tbody>
                        @endforelse
                </table>
            </div>
        </div>
    </div>

    {{-- Add Customer Tab --}}
    <div x-show="activeTab === 'add'" x-cloak>
        <div x-data="{
            recurringFrequency: 'daily',
            billingDates: [],
            monthlyDates: [1],
            showBillingModal: false,
            modalData: {},
            editingId: null,
            availableIbans: [],
            selectedIbanHash: '',
            loadingIbans: false,
            billingCurrency: 'EUR',
            async fetchIbans(currency) {
                if (!currency) { this.availableIbans = []; return; }
                this.loadingIbans = true;
                try {
                    const resp = await fetch(`{{ url('account/ibans-by-currency') }}?currency=${currency}`);
                    const data = await resp.json();
                    this.availableIbans = data.success ? data.ibans : [];
                    // Auto-select if only one option
                    if (this.availableIbans.length === 1) {
                        this.selectedIbanHash = this.availableIbans[0].hash;
                    } else if (!this.availableIbans.find(i => i.hash === this.selectedIbanHash)) {
                        this.selectedIbanHash = '';
                    }
                } catch (e) {
                    console.error('Error fetching IBANs:', e);
                    this.availableIbans = [];
                } finally {
                    this.loadingIbans = false;
                }
            },
            init() {
                this.fetchIbans(this.billingCurrency);
                this.$watch('billingCurrency', (val) => this.fetchIbans(val));
                this.$watch('editingCustomer', (c) => {
                    if (!c) return;
                    this.editingId = c.id;
                    this.$nextTick(() => {
                        const f = this.$refs.mandateForm;
                        f.querySelector('#customer_full_name').value = c.customer_full_name || '';
                        f.querySelector('#customer_primary_contact_name').value = c.customer_primary_contact_name || '';
                        f.querySelector('#customer_primary_contact_email').value = c.customer_primary_contact_email || '';
                        f.querySelector('#billing_currency').value = c.billing_currency || 'EUR';
                        this.billingCurrency = c.billing_currency || 'EUR';
                        f.querySelector('#billing_amount').value = c.billing_amount || '';
                        this.selectedIbanHash = c.settlement_iban_hash || '';
                        // Re-fetch IBANs for the currency and auto-select if needed
                        this.fetchIbans(this.billingCurrency).then(() => {
                            if (!this.selectedIbanHash && this.availableIbans.length === 1) {
                                this.selectedIbanHash = this.availableIbans[0].hash;
                            }
                        });
                        this.recurringFrequency = c.recurring_frequency || 'daily';
                        f.querySelector('#recurring_frequency').value = this.recurringFrequency;
                        // Populate frequency-specific fields
                        if (c.recurring_frequency === 'daily') {
                            f.querySelector('#billing_start_date').value = c.billing_start_date ? c.billing_start_date.split('T')[0] : '';
                        } else if (c.recurring_frequency === 'weekly' && c.billing_dates) {
                            f.querySelectorAll('input[name=&quot;billing_dates[]&quot;][type=&quot;checkbox&quot;]').forEach(cb => {
                                cb.checked = c.billing_dates.includes(cb.value) || c.billing_dates.includes(parseInt(cb.value));
                            });
                        } else if (c.recurring_frequency === 'monthly' && c.billing_dates) {
                            this.monthlyDates = c.billing_dates.filter(d => !isNaN(d)).map(d => parseInt(d));
                            if (this.monthlyDates.length === 0) this.monthlyDates = [1];
                        }
                        // Billing account details
                        f.querySelector('#billing_name_on_account').value = c.billing_name_on_account || '';
                        f.querySelector('#customer_bic').value = c.customer_bic || '';
                        f.querySelector('#customer_iban').value = c.customer_iban || '';
                        f.querySelector('#billing_bank_name').value = c.billing_bank_name || '';
                        this.bankName = c.billing_bank_name || '';
                    });
                });
            },
            clearForm() {
                this.editingId = null;
                editingCustomer = null;
                const f = this.$refs.mandateForm;
                f.reset();
                this.recurringFrequency = 'daily';
                this.monthlyDates = [1];
                this.bankName = '';
                this.billingCurrency = 'EUR';
                this.selectedIbanHash = '';
            },
            captureFormData() {
                const f = this.$refs.mandateForm;
                const freq = f.querySelector('#recurring_frequency')?.value || '';
                const dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                let weeklyDays = [];
                if (freq === 'weekly') {
                    f.querySelectorAll('input[name=&quot;billing_dates[]&quot;][type=&quot;checkbox&quot;]:checked').forEach(cb => {
                        weeklyDays.push(dayNames[parseInt(cb.value)]);
                    });
                }
                let monthlyDays = [];
                if (freq === 'monthly') {
                    monthlyDays = [...this.monthlyDates].map(d => 'Day ' + d);
                }
                this.modalData = {
                    customerName: f.querySelector('#customer_full_name')?.value || '',
                    contactEmail: f.querySelector('#customer_primary_contact_email')?.value || '',
                    currency: f.querySelector('#billing_currency')?.value || '',
                    amount: f.querySelector('#billing_amount')?.value || '',
                    frequency: freq,
                    startDate: f.querySelector('#billing_start_date')?.value || '',
                    weeklyDays: weeklyDays,
                    monthlyDays: monthlyDays,
                    bic: f.querySelector('#customer_bic')?.value || '',
                    iban: f.querySelector('#customer_iban')?.value || '',
                    bankName: f.querySelector('#billing_bank_name')?.value || '',
                    nameOnAccount: f.querySelector('#billing_name_on_account')?.value || '',
                    settlementIban: this.availableIbans.find(i => i.hash === this.selectedIbanHash)?.iban_number || '',
                    settlementName: this.availableIbans.find(i => i.hash === this.selectedIbanHash)?.friendly_name || '',
                };
            },
            submitForm() { this.$refs.mandateForm.submit(); },
            resetSubmitButton() {
                const btn = this.$refs.mandateForm.querySelector('button[type=&quot;submit&quot;]');
                if (btn && btn.dataset.originalText) {
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.originalText;
                    btn.style.opacity = '';
                    btn.style.cursor = '';
                }
            }
        }">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">
                    <span x-show="!editingId">{{ __translator('Send Mandate Invitation') }}</span>
                    <span x-show="editingId" x-cloak>{{ __translator('Edit Customer Details') }}</span>
                </h2>
                <button x-show="editingId" x-cloak type="button" @click="clearForm()" 
                        class="text-sm font-medium px-4 py-2 rounded-md"
                        style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                    {{ __translator('+ New Customer') }}
                </button>
            </div>
            
            <p class="text-sm opacity-70 mb-6">
                {{ __translator('Send a mandate invitation to a customer to authorize recurring payments. The customer will receive an email with a link to confirm their IBAN and BIC details.') }}
            </p>

            {{-- Mandate Invitation Form --}}
            <form method="POST" :action="editingId ? '{{ url('account/customers') }}/' + editingId + '/update' : '{{ route('account.customers.invite') }}'" class="space-y-6" x-ref="mandateForm" @submit.prevent="
                captureFormData();
                showBillingModal = true;
            ">
                @csrf
                <input type="hidden" name="editing_customer_id" :value="editingId || ''">

                {{-- Card 1: Customer Information --}}
                <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                    <h3 class="text-md font-semibold mb-4">{{ __translator('Customer Information') }}</h3>
                    
                    <div class="space-y-6">
                        {{-- Customer Full Name --}}
                        <div>
                            <label for="customer_full_name" class="block text-sm font-medium mb-2">
                                {{ __translator('Customer Full Name') }} *
                            </label>
                            <input type="text" 
                                   id="customer_full_name" 
                                   name="customer_full_name" 
                                   required
                                   class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                   style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                            <p class="text-xs opacity-60 mt-1">{{ __translator('The official name of the customer (business or individual)') }}</p>
                        </div>

                        {{-- Primary Contact Name --}}
                        <div>
                            <label for="customer_primary_contact_name" class="block text-sm font-medium mb-2">
                                {{ __translator('Primary Contact Name') }}
                            </label>
                            <input type="text" 
                                   id="customer_primary_contact_name" 
                                   name="customer_primary_contact_name" 
                                   class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                   style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                            <p class="text-xs opacity-60 mt-1">{{ __translator('Optional: The person to contact (if different from customer name)') }}</p>
                        </div>

                        {{-- Primary Contact Email --}}
                        <div>
                            <label for="customer_primary_contact_email" class="block text-sm font-medium mb-2">
                                {{ __translator('Primary Contact Email Address') }} *
                            </label>
                            <input type="email" 
                                   id="customer_primary_contact_email" 
                                   name="customer_primary_contact_email" 
                                   required
                                   class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                   style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                            <p class="text-xs opacity-60 mt-1">{{ __translator('The mandate invitation will be sent to this email address') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Billing Amount --}}
                <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                    <h3 class="text-md font-semibold mb-4">{{ __translator('Billing Amount') }}</h3>
                    
                    <div class="space-y-4">
                        {{-- Currency --}}
                        <div>
                            <label for="billing_currency" class="block text-sm font-medium mb-2">
                                {{ __translator('Billing Currency') }} *
                            </label>
                            <select id="billing_currency"
                                    name="billing_currency"
                                    x-model="billingCurrency"
                                    required
                                    class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                    style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                                <option value="EUR" selected>EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>

                        {{-- Billing Amount --}}
                        <div>
                            <label for="billing_amount" class="block text-sm font-medium mb-2">
                                {{ __translator('Billing Amount') }} *
                            </label>
                            <input type="number" 
                                   id="billing_amount" 
                                   name="billing_amount" 
                                   required
                                   step="0.01"
                                   min="0.01"
                                   class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                   style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                        </div>
                    </div>
                </div>

                {{-- Card 3: Recurring Payment Schedule --}}
                <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                    <h3 class="text-md font-semibold mb-4">{{ __translator('Recurring Payment Schedule') }}</h3>
                    
                    {{-- Recurring Frequency --}}
                    <div class="mb-6">
                        <label for="recurring_frequency" class="block text-sm font-medium mb-2">
                            {{ __translator('Payment Frequency') }} *
                        </label>
                        <select id="recurring_frequency" 
                                name="recurring_frequency"
                                x-model="recurringFrequency"
                                required
                                class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                            <option value="daily">{{ __translator('Daily') }}</option>
                            <option value="weekly">{{ __translator('Weekly') }}</option>
                            <option value="monthly">{{ __translator('Monthly') }}</option>
                        </select>
                    </div>

                    {{-- Billing Dates Based on Frequency --}}
                    <div class="mt-6">
                        <label class="block text-sm font-medium mb-2">
                            {{ __translator('Billing Dates') }} *
                        </label>
                        
                        {{-- Daily: Start Billing Date selection --}}
                        <div x-show="recurringFrequency === 'daily'" class="space-y-4">
                            <p class="text-xs opacity-70">{{ __translator('Payments will occur every day') }}</p>
                            <input type="hidden" name="billing_dates[]" value="daily" :disabled="recurringFrequency !== 'daily'">
                            
                            <div class="mt-4">
                                <label for="billing_start_date" class="block text-sm font-medium mb-2">
                                    {{ __translator('Start Billing Date') }} *
                                </label>
                                <input type="date" 
                                       id="billing_start_date" 
                                       name="billing_start_date" 
                                       :required="recurringFrequency === 'daily'"
                                       min="{{ date('Y-m-d') }}"
                                       class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                       style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                                <p class="text-xs opacity-60 mt-1">{{ __translator('The date when daily billing will begin') }}</p>
                            </div>
                        </div>

                        {{-- Weekly: Select Days of Week (Multiple) --}}
                        <div x-show="recurringFrequency === 'weekly'" class="space-y-3">
                            <p class="text-xs opacity-70 mb-3">{{ __translator('Select one or more days of the week for payments') }}</p>
                            <div class="grid grid-cols-7 gap-2">
                                <label class="flex flex-col items-center p-3 rounded-md cursor-pointer transition-colors"
                                       style="background-color: var(--content-background-color);">
                                    <input type="checkbox" name="billing_dates[]" value="1" class="mb-2" :disabled="recurringFrequency !== 'weekly'">
                                    <span class="text-sm">{{ __translator('Monday') }}</span>
                                </label>
                                <label class="flex flex-col items-center p-3 rounded-md cursor-pointer transition-colors"
                                       style="background-color: var(--content-background-color);">
                                    <input type="checkbox" name="billing_dates[]" value="2" class="mb-2" :disabled="recurringFrequency !== 'weekly'">
                                    <span class="text-sm">{{ __translator('Tuesday') }}</span>
                                </label>
                                <label class="flex flex-col items-center p-3 rounded-md cursor-pointer transition-colors"
                                       style="background-color: var(--content-background-color);">
                                    <input type="checkbox" name="billing_dates[]" value="3" class="mb-2" :disabled="recurringFrequency !== 'weekly'">
                                    <span class="text-sm">{{ __translator('Wednesday') }}</span>
                                </label>
                                <label class="flex flex-col items-center p-3 rounded-md cursor-pointer transition-colors"
                                       style="background-color: var(--content-background-color);">
                                    <input type="checkbox" name="billing_dates[]" value="4" class="mb-2" :disabled="recurringFrequency !== 'weekly'">
                                    <span class="text-sm">{{ __translator('Thursday') }}</span>
                                </label>
                                <label class="flex flex-col items-center p-3 rounded-md cursor-pointer transition-colors"
                                       style="background-color: var(--content-background-color);">
                                    <input type="checkbox" name="billing_dates[]" value="5" class="mb-2" :disabled="recurringFrequency !== 'weekly'">
                                    <span class="text-sm">{{ __translator('Friday') }}</span>
                                </label>
                                <label class="flex flex-col items-center p-3 rounded-md cursor-pointer transition-colors"
                                       style="background-color: var(--content-background-color);">
                                    <input type="checkbox" name="billing_dates[]" value="6" class="mb-2" :disabled="recurringFrequency !== 'weekly'">
                                    <span class="text-sm">{{ __translator('Saturday') }}</span>
                                </label>
                                <label class="flex flex-col items-center p-3 rounded-md cursor-pointer transition-colors"
                                       style="background-color: var(--content-background-color);">
                                    <input type="checkbox" name="billing_dates[]" value="0" class="mb-2" :disabled="recurringFrequency !== 'weekly'">
                                    <span class="text-sm">{{ __translator('Sunday') }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Monthly: Dynamic Date Addition --}}
                        <div x-show="recurringFrequency === 'monthly'" class="space-y-3">
                            <p class="text-xs opacity-70 mb-3">{{ __translator('Add one or more payment days of the month') }}</p>
                            
                            <template x-for="(date, index) in monthlyDates" :key="index">
                                <div class="flex gap-2 items-end">
                                    <div class="flex-1">
                                        <label class="block text-xs mb-1 opacity-70" x-text="'{{ __translator('Payment Day') }} ' + (index + 1)"></label>
                                        <select :name="recurringFrequency === 'monthly' ? 'billing_dates[]' : ''"
                                                x-model="monthlyDates[index]"
                                                :required="recurringFrequency === 'monthly'"
                                                :disabled="recurringFrequency !== 'monthly'"
                                                class="w-full px-3 py-2 rounded-md text-sm border-0"
                                                style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                                            @for($i = 1; $i <= 28; $i++)
                                            <option value="{{ $i }}">{{ __translator('Day') }} {{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <button type="button"
                                            x-show="monthlyDates.length > 1"
                                            @click="monthlyDates.splice(index, 1)"
                                            class="px-3 py-2 rounded-md text-sm font-medium opacity-70 hover:opacity-100"
                                            style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                                        {{ __translator('Remove') }}
                                    </button>
                                </div>
                            </template>
                            
                            <button type="button"
                                    @click="monthlyDates.push(1)"
                                    class="px-4 py-2 rounded-md text-sm font-medium"
                                    style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                                + {{ __translator('Add Another Payment Day') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Billing Account Details --}}
                <div class="rounded-lg p-6" style="background-color: var(--card-background-color);" x-data="bicLookup()">
                    <h3 class="text-md font-semibold mb-4">{{ __translator('Billing Account Details') }}</h3>
                    
                    <div class="space-y-4">
                        {{-- Name on Account --}}
                        <div>
                            <label for="billing_name_on_account" class="block text-sm font-medium mb-2">
                                {{ __translator('Name on Account') }}
                            </label>
                            <input type="text" 
                                   id="billing_name_on_account" 
                                   name="billing_name_on_account" 
                                   class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                   style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                        </div>

                        {{-- Account BIC --}}
                        <div>
                            <label for="customer_bic" class="block text-sm font-medium mb-2">
                                {{ __translator('Bank BIC') }}
                            </label>
                            <input type="text" 
                                   id="customer_bic" 
                                   name="customer_bic" 
                                   maxlength="11"
                                   @blur="lookupFromBic($event.target.value)"
                                   class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                   style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                            <p class="text-xs opacity-60 mt-1">{{ __translator('Bank name will be auto-detected from BIC or IBAN') }}</p>
                        </div>

                        {{-- Account IBAN --}}
                        <div>
                            <label for="customer_iban" class="block text-sm font-medium mb-2">
                                {{ __translator('Account IBAN') }}
                            </label>
                            <input type="text" 
                                   id="customer_iban" 
                                   name="customer_iban" 
                                   maxlength="34"
                                   @blur="validateAndLookupIban($event.target.value)"
                                   class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                   style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                            <p x-show="ibanError" x-text="ibanError" class="text-xs mt-1" style="color: var(--status-error-color);"></p>
                            <p x-show="ibanValid" class="text-xs mt-1" style="color: var(--brand-primary-color);">{{ __translator('IBAN checksum valid') }}</p>
                        </div>

                        {{-- Bank Name (auto-populated) --}}
                        <div>
                            <label for="billing_bank_name" class="block text-sm font-medium mb-2">
                                {{ __translator('Bank Name') }}
                                <span x-show="isLoading" class="inline-flex items-center ml-2">
                                    <svg class="animate-spin h-4 w-4" style="color: var(--brand-primary-color);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-xs ml-1 opacity-70">{{ __translator('Identifying bank...') }}</span>
                                </span>
                            </label>
                            <input type="text" 
                                   id="billing_bank_name" 
                                   name="billing_bank_name" 
                                   x-model="bankName"
                                   :readonly="isLoading"
                                   class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                   style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                            <p x-show="lookupError" x-text="lookupError" class="text-xs mt-1" style="color: var(--status-error-color);"></p>
                        </div>
                    </div>
                </div>

                {{-- Card 5: Settlement Ledger --}}
                <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                    <h3 class="text-md font-semibold mb-4">{{ __translator('Settlement Account') }}</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="settlement_iban_hash" class="block text-sm font-medium mb-2">
                                {{ __translator('Settlement Account IBAN') }} *
                                <span x-show="loadingIbans" class="inline-flex items-center ml-2">
                                    <svg class="animate-spin h-4 w-4" style="color: var(--brand-primary-color);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </label>
                            <select id="settlement_iban_hash"
                                    name="settlement_iban_hash"
                                    x-model="selectedIbanHash"
                                    required
                                    class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                                    style="background-color: var(--content-background-color); color: var(--sidebar-text-color);">
                                <option value="" :disabled="availableIbans.length === 1">{{ __translator('-- Select Settlement IBAN --') }}</option>
                                <template x-for="iban in availableIbans" :key="iban.hash">
                                    <option :value="iban.hash" x-text="iban.currency + ' : ' + iban.iban_number + ' (' + iban.friendly_name + ')'"></option>
                                </template>
                            </select>
                            <p class="text-xs opacity-60 mt-1" x-show="availableIbans.length === 0 && !loadingIbans">{{ __translator('No IBANs with ledger IDs found for the selected currency. Please configure IBANs in the administrator panel first.') }}</p>
                            <p class="text-xs opacity-60 mt-1" x-show="availableIbans.length > 0">{{ __translator('Select which IBAN account the direct debit funds for this mandate will be deposited into.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Card 6: Submit Button --}}
                <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                    <div class="flex justify-center">
                        <button type="submit" 
                                style="background-color: var(--brand-primary-color); color: var(--button-text-color); padding-top: 14px; padding-bottom: 14px; padding-left: 100px; padding-right: 100px; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; border: none;">
                            <span x-show="!editingId">{{ __translator('Send Mandate Invitation') }}</span>
                            <span x-show="editingId" x-cloak>{{ __translator('Update Customer') }}</span>
                        </button>
                    </div>
                </div>
            </form>

            {{-- Mandate Confirmation Modal --}}
            <template x-teleport="body">
            <div x-show="showBillingModal" x-cloak
                 style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background-color: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px);">
                <div @click.away="showBillingModal = false; resetSubmitButton()"
                     style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: var(--card-background-color); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; max-width: 420px; width: calc(100% - 48px); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                    
                    {{-- Modal Header --}}
                    <div style="padding: 28px 28px 16px 28px;">
                        <div style="font-size: 16px; font-weight: 600; color: var(--sidebar-text-color);">
                            <span x-show="!editingId">{{ __translator('Confirm Mandate Invitation') }}</span>
                            <span x-show="editingId" x-cloak>{{ __translator('Confirm Customer Update') }}</span>
                        </div>
                        <div style="font-size: 12px; margin-top: 4px; color: var(--sidebar-text-color); opacity: 0.5;">
                            <span x-show="!editingId">{{ __translator('Please review the details below before sending.') }}</span>
                            <span x-show="editingId" x-cloak>{{ __translator('Please review the updated details below.') }}</span>
                        </div>
                    </div>

                    {{-- Required Details --}}
                    <div style="padding: 0 28px 16px 28px;">
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--sidebar-text-color); opacity: 0.4; margin-bottom: 10px;">
                            {{ __translator('Required') }}
                        </div>
                        <div style="background-color: var(--content-background-color); border-radius: 10px; padding: 16px;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Customer') }}</span>
                                <span style="color: var(--sidebar-text-color); font-weight: 500;" x-text="modalData.customerName || '—'"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Email') }}</span>
                                <span style="color: var(--sidebar-text-color); font-weight: 500;" x-text="modalData.contactEmail || '—'"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Amount') }}</span>
                                <span style="color: var(--sidebar-text-color); font-weight: 500;" x-text="(modalData.currency || '') + ' ' + (modalData.amount || '—')"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Frequency') }}</span>
                                <span style="color: var(--sidebar-text-color); font-weight: 500; text-transform: capitalize;" x-text="modalData.frequency || '—'"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Settlement Account IBAN') }}</span>
                                <span :style="modalData.settlementIban ? 'color: var(--sidebar-text-color); font-weight: 500; font-size: 11px; font-family: monospace;' : 'color: #ef4444; font-weight: 500;'" x-text="modalData.settlementIban || '{{ __translator('Not Selected') }}'"></span>
                            </div>
                            <template x-if="modalData.settlementName">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                    <span style="color: var(--sidebar-text-color); opacity: 0.55; white-space: nowrap;">{{ __translator('Settlement Account Name') }}</span>
                                    <span style="color: var(--sidebar-text-color); font-weight: 500;" x-text="modalData.settlementName || ''"></span>
                                </div>
                            </template>
                            <div x-show="modalData.frequency === 'daily' && modalData.startDate">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                    <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Start Date') }}</span>
                                    <span style="color: var(--sidebar-text-color); font-weight: 500; text-align: right;" x-text="modalData.startDate || '—'"></span>
                                </div>
                            </div>
                            <div x-show="modalData.frequency === 'weekly' && modalData.weeklyDays?.length">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                    <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Days') }}</span>
                                    <span style="color: var(--sidebar-text-color); font-weight: 500; text-align: right;" x-text="(modalData.weeklyDays || []).join(', ')"></span>
                                </div>
                            </div>
                            <div x-show="modalData.frequency === 'monthly' && modalData.monthlyDays?.length">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                    <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Payment Days') }}</span>
                                    <span style="color: var(--sidebar-text-color); font-weight: 500; text-align: right;" x-text="(modalData.monthlyDays || []).join(', ')"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Optional Details --}}
                    <div style="padding: 0 28px 20px 28px;">
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--sidebar-text-color); opacity: 0.4; margin-bottom: 10px;">
                            {{ __translator('Optional') }}
                        </div>
                        <div style="background-color: var(--content-background-color); border-radius: 10px; padding: 16px;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Name on Account') }}</span>
                                <span :style="modalData.nameOnAccount?.trim() ? 'color: var(--sidebar-text-color); font-weight: 500;' : 'color: #ef4444; font-weight: 500;'" x-text="modalData.nameOnAccount?.trim() || '{{ __translator('Not Set') }}'"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Bank BIC') }}</span>
                                <span :style="modalData.bic?.trim() ? 'color: var(--sidebar-text-color); font-weight: 500;' : 'color: #ef4444; font-weight: 500;'" x-text="modalData.bic?.trim() || '{{ __translator('Not Set') }}'"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Account IBAN') }}</span>
                                <span :style="modalData.iban?.trim() ? 'color: var(--sidebar-text-color); font-weight: 500;' : 'color: #ef4444; font-weight: 500;'" x-text="modalData.iban?.trim() || '{{ __translator('Not Set') }}'"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span style="color: var(--sidebar-text-color); opacity: 0.55;">{{ __translator('Bank Name') }}</span>
                                <span :style="modalData.bankName?.trim() ? 'color: var(--sidebar-text-color); font-weight: 500;' : 'color: #ef4444; font-weight: 500;'" x-text="modalData.bankName?.trim() || '{{ __translator('Not Set') }}'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Actions --}}
                    <div style="padding: 8px 28px 28px 28px; display: flex; justify-content: space-between;">
                        <button @click="showBillingModal = false; resetSubmitButton()"
                                type="button"
                                style="background-color: var(--content-background-color); color: var(--sidebar-text-color); padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 500; border: none; cursor: pointer;">
                            {{ __translator('Edit') }}
                        </button>
                        <button @click="showBillingModal = false; submitForm()"
                                type="button"
                                style="background-color: var(--brand-primary-color); color: var(--button-text-color); padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 500; border: none; cursor: pointer;">
                            <span x-show="!editingId">{{ __translator('Continue & Send') }}</span>
                            <span x-show="editingId" x-cloak>{{ __translator('Save Changes') }}</span>
                        </button>
                    </div>
                </div>
            </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bicLookup() {
    return {
        bankName: '',
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
                const response = await fetch('/account/lookup-bank-from-bic', {
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
@endpush
