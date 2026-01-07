@extends('layouts.platform')

@section('content')
{{-- Team Management Page --}}

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Team Members</h1>
        <a href="{{ route('account.team.invite') }}" 
           class="px-4 py-2 rounded-md font-medium"
           style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
            + Invite Member
        </a>
    </div>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    {{-- Current Members --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Current Members ({{ $memberships->count() }})</h2>

        <div class="space-y-4">
            @foreach($memberships as $membership)
            <div class="p-4 rounded-lg" style="background-color: var(--content-background-color);">
                <div class="flex justify-between items-start">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" 
                             style="background-color: var(--sidebar-hover-background-color);">
                            {{ strtoupper(substr($membership->platform_member->member_first_name ?: $membership->platform_member->login_email_address, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-medium">
                                {{ $membership->platform_member->full_name }}
                                @if($membership->platform_member_id === auth()->id())
                                    <span class="text-xs opacity-60">(you)</span>
                                @endif
                            </p>
                            <p class="text-sm opacity-60">{{ $membership->platform_member->login_email_address }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 rounded text-xs" style="background-color: var(--sidebar-hover-background-color);">
                            {{ ucfirst(str_replace('_', ' ', $membership->account_membership_role)) }}
                        </span>
                        @if($membership->isActive())
                            <span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-success-color); color: white;">Active</span>
                        @elseif($membership->isRevoked())
                            <span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-error-color); color: white;">Revoked</span>
                        @endif
                    </div>
                </div>

                {{-- Permissions (editable for non-owners, active members only) --}}
                @if($membership->account_membership_role !== 'account_owner')
                <div class="mt-4 pt-4 border-t" style="border-color: var(--sidebar-hover-background-color);">
                    @if($membership->isActive())
                    <form method="POST" action="{{ route('account.team.permissions', $membership->id) }}">
                        @csrf
                        <p class="text-sm font-medium mb-2">Permissions:</p>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            @foreach($allPermissions as $perm)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="permissions[]" 
                                       value="{{ $perm }}"
                                       {{ in_array($perm, $membership->granted_permission_slugs ?? []) ? 'checked' : '' }}
                                       class="mr-2">
                                {{ ucfirst(str_replace(['can_', '_'], ['', ' '], $perm)) }}
                            </label>
                            @endforeach
                        </div>
                        
                        <div class="mt-4 flex space-x-2">
                            <button type="submit" 
                                    class="px-3 py-1 rounded text-sm"
                                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                Save Permissions
                            </button>
                            
                            @if($membership->platform_member_id !== auth()->id())
                            <button type="button" 
                                    onclick="if(confirm('Revoke access for this member?')) document.getElementById('revoke-{{ $membership->id }}').submit();"
                                    class="px-3 py-1 rounded text-sm"
                                    style="background-color: var(--status-error-color); color: white;">
                                Revoke Access
                            </button>
                            @endif
                        </div>
                    </form>
                    <form id="revoke-{{ $membership->id }}" method="POST" action="{{ route('account.team.revoke', $membership->id) }}" class="hidden">@csrf</form>
                    @elseif($membership->isRevoked())
                    <p class="text-sm opacity-60 mb-3">This member's access has been revoked.</p>
                    @if($membership->platform_member_id !== auth()->id())
                    <button type="button" 
                            onclick="document.getElementById('reactivate-{{ $membership->id }}').submit();"
                            class="px-3 py-1 rounded text-sm"
                            style="background-color: var(--status-success-color); color: white;">
                        Restore Access
                    </button>
                    <form id="reactivate-{{ $membership->id }}" method="POST" action="{{ route('account.team.reactivate', $membership->id) }}" class="hidden">@csrf</form>
                    @endif
                    @endif
                </div>
                @else
                <p class="mt-4 text-sm opacity-60">Account owner has full access to all features.</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Pending Invitations --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Pending Invitations</h2>
        
        @if($pendingInvitations->isEmpty())
            <p class="text-sm opacity-60">No pending invitations.</p>
        @else
            <div class="space-y-3">
                @foreach($pendingInvitations as $invitation)
                <div class="flex justify-between items-center p-3 rounded" style="background-color: var(--content-background-color);">
                    <div>
                        <p class="font-medium">{{ $invitation->invited_email_address }}</p>
                        <p class="text-sm opacity-60">Invited {{ $invitation->created_at_timestamp->diffForHumans() }}</p>
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 rounded text-sm" style="background-color: var(--sidebar-hover-background-color);">
                            Resend
                        </button>
                        <button class="px-3 py-1 rounded text-sm" style="background-color: var(--status-error-color); color: white;">
                            Cancel
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
