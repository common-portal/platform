@extends('layouts.platform')

@section('content')
{{-- Administrator Panel --}}

<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Administrator Panel</h1>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    @if(session('admin_impersonating_from'))
    <div class="mb-4 p-3 rounded-md text-sm flex justify-between items-center" style="background-color: var(--status-warning-color); color: #1a1a2e;">
        <span><strong>Impersonation Mode:</strong> You are viewing as another user/account.</span>
        <a href="{{ route('admin.exit-impersonation') }}" class="underline font-medium">Exit Impersonation</a>
    </div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">{{ $stats['total_members'] }}</p>
            <p class="text-sm opacity-70">Total Members</p>
        </div>
        
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">{{ $stats['total_accounts'] }}</p>
            <p class="text-sm opacity-70">Total Accounts</p>
        </div>
        
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">{{ $stats['business_accounts'] }}</p>
            <p class="text-sm opacity-70">Business Accounts</p>
        </div>
        
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">{{ $stats['pending_invitations'] }}</p>
            <p class="text-sm opacity-70">Pending Invitations</p>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Administration</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.members') }}" class="p-4 rounded-lg text-center hover:opacity-80" style="background-color: var(--content-background-color);">
                <p class="font-medium">Members</p>
                <p class="text-sm opacity-70">Manage users</p>
            </a>
            <a href="{{ route('admin.accounts') }}" class="p-4 rounded-lg text-center hover:opacity-80" style="background-color: var(--content-background-color);">
                <p class="font-medium">Accounts</p>
                <p class="text-sm opacity-70">View accounts</p>
            </a>
            <a href="{{ route('admin.transactions') }}" class="p-4 rounded-lg text-center hover:opacity-80" style="background-color: var(--content-background-color);">
                <p class="font-medium">Transactions</p>
                <p class="text-sm opacity-70">Record transactions</p>
            </a>
            <a href="{{ route('admin.theme') }}" class="p-4 rounded-lg text-center hover:opacity-80" style="background-color: var(--content-background-color);">
                <p class="font-medium">Theme</p>
                <p class="text-sm opacity-70">Platform branding</p>
            </a>
            <a href="{{ route('admin.menu-items') }}" class="p-4 rounded-lg text-center hover:opacity-80" style="background-color: var(--content-background-color);">
                <p class="font-medium">Menu Items</p>
                <p class="text-sm opacity-70">Toggle features</p>
            </a>
            <a href="{{ route('admin.iban-host-banks') }}" class="p-4 rounded-lg text-center hover:opacity-80" style="background-color: var(--content-background-color);">
                <p class="font-medium">IBAN Host Banks</p>
                <p class="text-sm opacity-70">Manage host banks</p>
            </a>
            <a href="{{ route('admin.ibans') }}" class="p-4 rounded-lg text-center hover:opacity-80" style="background-color: var(--content-background-color);">
                <p class="font-medium">IBANs</p>
                <p class="text-sm opacity-70">Manage bank accounts</p>
            </a>
            <a href="{{ route('admin.wallets') }}" class="p-4 rounded-lg text-center hover:opacity-80" style="background-color: var(--content-background-color);">
                <p class="font-medium">Wallets</p>
                <p class="text-sm opacity-70">Manage crypto wallets</p>
            </a>
        </div>
    </div>
</div>
@endsection
