@extends('layouts.platform')

@section('content')
{{-- Support Tickets Module --}}

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Support Tickets</h1>
        <a href="{{ route('modules.support.create') }}" 
           class="px-4 py-2 rounded-md text-sm font-medium"
           style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
            + New Ticket
        </a>
    </div>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        @if($tickets->count() > 0)
        <table class="w-full">
            <thead>
                <tr class="text-left text-sm opacity-60 border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <th class="pb-3">Subject</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3">Created</th>
                    <th class="pb-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $ticket)
                <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                    <td class="py-4">
                        <p class="font-medium">{{ Str::limit($ticket->ticket_subject_line, 50) }}</p>
                    </td>
                    <td class="py-4">
                        @php
                            $statusColors = [
                                'ticket_open' => 'var(--status-info-color)',
                                'ticket_in_progress' => 'var(--status-warning-color)',
                                'ticket_resolved' => 'var(--status-success-color)',
                                'ticket_closed' => 'var(--sidebar-hover-background-color)',
                            ];
                            $statusLabels = [
                                'ticket_open' => 'Open',
                                'ticket_in_progress' => 'In Progress',
                                'ticket_resolved' => 'Resolved',
                                'ticket_closed' => 'Closed',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded text-xs text-white" 
                              style="background-color: {{ $statusColors[$ticket->ticket_status] ?? 'var(--sidebar-hover-background-color)' }};">
                            {{ $statusLabels[$ticket->ticket_status] ?? $ticket->ticket_status }}
                        </span>
                    </td>
                    <td class="py-4 text-sm opacity-70">
                        {{ $ticket->created_at_timestamp->format('M j, Y') }}
                    </td>
                    <td class="py-4 text-right">
                        <a href="{{ route('modules.support.show', $ticket->id) }}" 
                           class="text-sm hover:underline"
                           style="color: var(--brand-primary-color);">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $tickets->links() }}
        </div>
        @else
        <div class="p-8 text-center">
            <p class="text-sm opacity-60">No support tickets yet.</p>
            <p class="text-xs opacity-40 mt-2">Create a ticket if you need assistance.</p>
        </div>
        @endif
    </div>
</div>
@endsection
