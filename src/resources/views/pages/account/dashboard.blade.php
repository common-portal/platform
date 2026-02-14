@extends('layouts.platform')

@section('content')
{{-- Account Dashboard Page --}}

<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">{{ __translator('Dashboard') }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">{{ $transactionCount ?? 0 }}</p>
            <p class="text-sm opacity-70">{{ __translator('Transactions (Count)') }}</p>
        </div>
        
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">{{ number_format($transactionTotal ?? 0, 2) }}</p>
            <p class="text-sm opacity-70">{{ __translator('Transactions (Amount)') }}</p>
        </div>
        
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--status-success-color);">{{ __translator('Active') }}</p>
            <p class="text-sm opacity-70">{{ __translator('Account Status') }}</p>
        </div>
    </div>

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">{{ __translator('Recent Activity') }}</h2>
        <p class="opacity-70">{{ __translator('No recent activity to display.') }}</p>
    </div>
</div>
@endsection
