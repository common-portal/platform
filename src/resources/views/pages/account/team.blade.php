@extends('layouts.platform')

@section('content')
{{-- Team Management Page --}}

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Team</h1>
        <button class="px-4 py-2 rounded-md font-medium"
                style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
            + Invite Member
        </button>
    </div>

    {{-- Team Members List --}}
    <div class="rounded-lg overflow-hidden" style="background-color: var(--card-background-color);">
        <table class="w-full">
            <thead>
                <tr style="background-color: var(--sidebar-hover-background-color);">
                    <th class="px-4 py-3 text-left text-sm font-medium">Member</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Role</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                    <th class="px-4 py-3 text-right text-sm font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teamMembers ?? [] as $member)
                <tr class="border-t" style="border-color: var(--sidebar-hover-background-color);">
                    <td class="px-4 py-3">
                        <div>
                            <p class="font-medium">{{ $member->full_name }}</p>
                            <p class="text-sm opacity-70">{{ $member->login_email_address }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm">{{ ucfirst(str_replace('_', ' ', $member->pivot->account_membership_role ?? 'Member')) }}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 rounded text-xs" 
                              style="background-color: var(--status-success-color); color: white;">
                            Active
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button class="text-sm opacity-70 hover:opacity-100">Edit</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center opacity-70">
                        No team members yet. Invite someone to get started.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
