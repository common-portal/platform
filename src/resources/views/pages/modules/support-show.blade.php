@extends('layouts.platform')

@section('content')
{{-- View Support Ticket --}}

<div class="max-w-3xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="{{ route('modules.support.index') }}" class="text-sm opacity-70 hover:opacity-100 mr-4">← Back</a>
        <h1 class="text-2xl font-bold">Ticket #{{ $ticket->id }}</h1>
    </div>

    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">{{ $ticket->ticket_subject_line }}</h2>
                <p class="text-sm opacity-60 mt-1">
                    Created {{ $ticket->created_at_timestamp->format('M j, Y \a\t g:i A') }}
                    @if($ticket->created_by_member)
                        by {{ $ticket->created_by_member->full_name }}
                    @endif
                </p>
            </div>
            <div>
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
                <span class="px-3 py-1 rounded text-sm text-white" 
                      style="background-color: {{ $statusColors[$ticket->ticket_status] ?? 'var(--sidebar-hover-background-color)' }};">
                    {{ $statusLabels[$ticket->ticket_status] ?? $ticket->ticket_status }}
                </span>
            </div>
        </div>

        @if($ticket->assigned_to_administrator)
        <p class="text-sm opacity-70 mb-4">
            Assigned to: {{ $ticket->assigned_to_administrator->full_name }}
        </p>
        @endif

        <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
            <p class="text-sm whitespace-pre-wrap">{{ $ticket->ticket_description_body }}</p>
        </div>
    </div>

    {{-- Ticket Responses (placeholder for future) --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <h3 class="text-md font-semibold mb-4">Responses</h3>
        <div class="p-8 text-center rounded-md" style="background-color: var(--content-background-color);">
            <p class="text-sm opacity-60">No responses yet.</p>
            <p class="text-xs opacity-40 mt-2">Our team will respond to your ticket shortly.</p>
        </div>
    </div>
</div>
@endsection
