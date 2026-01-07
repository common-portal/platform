@extends('layouts.platform')

@section('content')
{{-- Transactions History Module --}}

<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Transaction History</h1>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        @if($transactions->count() > 0)
        <table class="w-full">
            <thead>
                <tr class="text-left text-sm opacity-60 border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <th class="pb-3">Date</th>
                    <th class="pb-3">Description</th>
                    <th class="pb-3">Type</th>
                    <th class="pb-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <td class="py-4 text-sm">{{ $transaction->created_at->format('M j, Y') }}</td>
                    <td class="py-4">{{ $transaction->description }}</td>
                    <td class="py-4">
                        <span class="px-2 py-1 rounded text-xs" style="background-color: var(--sidebar-hover-background-color);">
                            {{ $transaction->type }}
                        </span>
                    </td>
                    <td class="py-4 text-right font-medium">
                        ${{ number_format($transaction->amount / 100, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <p class="text-sm opacity-60">No transactions yet.</p>
            <p class="text-xs opacity-40 mt-2">Your transaction history will appear here.</p>
        </div>
        @endif
    </div>
</div>
@endsection
