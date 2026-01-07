@extends('layouts.platform')

@section('content')
{{-- Billing History Module --}}

<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Billing History</h1>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        @if($invoices->count() > 0)
        <table class="w-full">
            <thead>
                <tr class="text-left text-sm opacity-60 border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <th class="pb-3">Invoice</th>
                    <th class="pb-3">Date</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3 text-right">Amount</th>
                    <th class="pb-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <td class="py-4 font-medium">{{ $invoice->invoice_number }}</td>
                    <td class="py-4 text-sm">{{ $invoice->created_at->format('M j, Y') }}</td>
                    <td class="py-4">
                        @if($invoice->status === 'paid')
                        <span class="px-2 py-1 rounded text-xs text-white" style="background-color: var(--status-success-color);">
                            Paid
                        </span>
                        @else
                        <span class="px-2 py-1 rounded text-xs text-white" style="background-color: var(--status-warning-color);">
                            {{ ucfirst($invoice->status) }}
                        </span>
                        @endif
                    </td>
                    <td class="py-4 text-right font-medium">
                        ${{ number_format($invoice->amount / 100, 2) }}
                    </td>
                    <td class="py-4 text-right">
                        <a href="#" class="text-sm hover:underline" style="color: var(--brand-primary-color);">
                            Download
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm opacity-60">No invoices yet.</p>
            <p class="text-xs opacity-40 mt-2">Your billing history will appear here.</p>
        </div>
        @endif
    </div>
</div>
@endsection
