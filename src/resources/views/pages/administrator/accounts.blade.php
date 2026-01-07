@extends('layouts.platform')

@section('content')
{{-- Admin Accounts Page --}}

<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">All Accounts</h1>
        <a href="{{ route('admin.index') }}" class="text-sm opacity-70 hover:opacity-100">← Back to Admin</a>
    </div>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <table class="w-full">
            <thead>
                <tr class="text-left text-sm opacity-60 border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <th class="pb-3">Account</th>
                    <th class="pb-3">Type</th>
                    <th class="pb-3">Owner</th>
                    <th class="pb-3">Created</th>
                    <th class="pb-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <td class="py-4">
                        <p class="font-medium">{{ $account->account_display_name }}</p>
                        <p class="text-sm opacity-60">{{ $account->account_slug }}</p>
                    </td>
                    <td class="py-4">
                        @if($account->account_type === 'personal_individual')
                            <span class="px-2 py-1 rounded text-xs" style="background-color: var(--sidebar-hover-background-color);">Personal</span>
                        @else
                            <span class="px-2 py-1 rounded text-xs" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">Business</span>
                        @endif
                    </td>
                    <td class="py-4 text-sm">
                        @php
                            $owner = $account->account_memberships->firstWhere('account_membership_role', 'account_owner')?->platform_member;
                        @endphp
                        @if($owner)
                            {{ $owner->full_name }}
                        @else
                            <span class="opacity-60">—</span>
                        @endif
                    </td>
                    <td class="py-4 text-sm opacity-70">{{ $account->created_at_timestamp->format('M j, Y') }}</td>
                    <td class="py-4 text-right">
                        <form method="POST" action="{{ route('admin.accounts.impersonate', $account->id) }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="px-3 py-1 rounded text-sm"
                                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                View As
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $accounts->links() }}
        </div>
    </div>
</div>
@endsection
