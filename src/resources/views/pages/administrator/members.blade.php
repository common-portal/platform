@extends('layouts.platform')

@section('content')
{{-- Admin Members Page --}}

<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Platform Members</h1>
        <a href="{{ route('admin.index') }}" class="text-sm opacity-70 hover:opacity-100">← Back to Admin</a>
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

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <table class="w-full">
            <thead>
                <tr class="text-left text-sm opacity-60 border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <th class="pb-3">Member</th>
                    <th class="pb-3">Joined</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $member)
                <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <td class="py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" 
                                 style="background-color: var(--sidebar-hover-background-color);">
                                {{ strtoupper(substr($member->member_first_name ?: $member->login_email_address, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium">
                                    {{ $member->full_name }}
                                    @if($member->id === auth()->id())
                                        <span class="text-xs opacity-60">(you)</span>
                                    @endif
                                </p>
                                <p class="text-sm opacity-60">{{ $member->login_email_address }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 text-sm opacity-70">{{ $member->created_at_timestamp->format('M j, Y') }}</td>
                    <td class="py-4">
                        @if($member->is_platform_administrator)
                            <span class="px-2 py-1 rounded text-xs" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">Admin</span>
                        @else
                            <span class="px-2 py-1 rounded text-xs" style="background-color: var(--sidebar-hover-background-color);">Member</span>
                        @endif
                    </td>
                    <td class="py-4 text-right">
                        <div class="flex justify-end space-x-2">
                            @if($member->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.members.toggle-admin', $member->id) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('{{ $member->is_platform_administrator ? 'Revoke admin access?' : 'Grant admin access?' }}')"
                                        class="px-3 py-1 rounded text-sm"
                                        style="background-color: var(--sidebar-hover-background-color);">
                                    {{ $member->is_platform_administrator ? 'Revoke Admin' : 'Make Admin' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.members.impersonate', $member->id) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-1 rounded text-sm"
                                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                    View As
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $members->links() }}
        </div>
    </div>
</div>
@endsection
