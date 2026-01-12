@extends('layouts.platform')

@section('content')
{{-- Support Tickets Module - Accordion View --}}

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

<div class="max-w-4xl mx-auto" x-data="{ expandedTicket: null }">
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

    @if($tickets->count() > 0)
    <div class="space-y-2">
        @foreach($tickets as $ticket)
        <div class="rounded-lg overflow-hidden" style="background-color: var(--card-background-color);">
            {{-- Ticket Header (clickable) --}}
            <div class="flex items-center justify-between px-4 py-3 cursor-pointer hover:opacity-90 transition-opacity"
                 @click="expandedTicket = expandedTicket === {{ $ticket->id }} ? null : {{ $ticket->id }}"
                 style="border-bottom: 1px solid var(--content-background-color);">
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <span class="flex-shrink-0 inline-block transition-transform duration-200" 
                          style="width: 16px; height: 16px;"
                          :style="expandedTicket === {{ $ticket->id }} ? 'transform: rotate(90deg)' : ''">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                    <span class="px-2 py-1 rounded text-xs text-white flex-shrink-0" 
                          style="background-color: {{ $statusColors[$ticket->ticket_status] ?? 'var(--sidebar-hover-background-color)' }};">
                        {{ $statusLabels[$ticket->ticket_status] ?? $ticket->ticket_status }}
                    </span>
                    <span class="text-sm font-medium truncate">{{ $ticket->ticket_subject_line }}</span>
                    @if($ticket->attachments_count > 0)
                    <span class="px-2 py-0.5 rounded text-xs flex-shrink-0" style="background-color: var(--sidebar-hover-background-color);">
                        📎 {{ $ticket->attachments_count }}
                    </span>
                    @endif
                    @if($ticket->messages_count > 0)
                    <span class="px-2 py-0.5 rounded text-xs flex-shrink-0" style="background-color: var(--sidebar-hover-background-color);">
                        💬 {{ $ticket->messages_count }}
                    </span>
                    @endif
                </div>
                <div class="flex items-center gap-3 flex-shrink-0 ml-2">
                    <span class="text-xs opacity-50">{{ $ticket->created_at_timestamp->format('M j, Y') }}</span>
                    <span class="text-xs opacity-40">#{{ $ticket->id }}</span>
                </div>
            </div>
            
            {{-- Ticket Detail Panel (expandable) --}}
            <div x-show="expandedTicket === {{ $ticket->id }}"
                 x-collapse
                 x-cloak>
                <div class="p-4 space-y-4" style="background-color: var(--content-background-color);">
                    {{-- Meta Info --}}
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div>
                            <span class="block text-xs uppercase tracking-wide opacity-50 mb-1">Created</span>
                            <span>{{ $ticket->created_at_timestamp->format('M j, Y \a\t g:i A') }}</span>
                        </div>
                        @if($ticket->created_by_member)
                        <div>
                            <span class="block text-xs uppercase tracking-wide opacity-50 mb-1">By</span>
                            <span>{{ $ticket->created_by_member->full_name }}</span>
                        </div>
                        @endif
                        @if($ticket->assigned_to_administrator)
                        <div>
                            <span class="block text-xs uppercase tracking-wide opacity-50 mb-1">Assigned To</span>
                            <span>{{ $ticket->assigned_to_administrator->full_name }}</span>
                        </div>
                        @endif
                    </div>
                    
                    {{-- Description --}}
                    <div class="p-4 rounded-md" style="background-color: var(--card-background-color);">
                        <label class="block text-xs uppercase tracking-wide opacity-50 mb-2">Description</label>
                        <div class="text-sm whitespace-pre-wrap">{{ $ticket->ticket_description_body }}</div>
                    </div>
                    
                    {{-- Attachments --}}
                    @if($ticket->attachments && $ticket->attachments->count() > 0)
                    <div>
                        <label class="block text-xs uppercase tracking-wide opacity-50 mb-2">Attachments ({{ $ticket->attachments->count() }})</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($ticket->attachments as $att)
                            <a href="{{ $att->url }}" 
                               download="{{ $att->original_filename }}"
                               class="inline-flex items-center gap-2 px-3 py-1.5 rounded text-sm hover:opacity-80 transition-opacity"
                               style="background-color: var(--sidebar-hover-background-color);">
                                @php
                                    $ext = strtolower(pathinfo($att->original_filename, PATHINFO_EXTENSION));
                                    $icons = ['pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'txt' => '📃', 'zip' => '📦', 'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️'];
                                @endphp
                                <span class="text-sm">{{ $icons[$ext] ?? '📎' }}</span>
                                <span class="truncate max-w-[200px]">{{ $att->original_filename }}</span>
                                <span class="text-xs opacity-50">({{ number_format($att->file_size_bytes / 1024, 1) }} KB)</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    {{-- Message Thread --}}
                    <div class="border-t pt-4" style="border-color: var(--sidebar-hover-background-color);">
                        <label class="block text-xs uppercase tracking-wide opacity-50 mb-2">Conversation</label>
                        
                        @if($ticket->messages && $ticket->messages->count() > 0)
                        <div class="space-y-3 mb-4">
                            @foreach($ticket->messages as $message)
                            <div class="p-3 rounded-md {{ $message->isAdminResponse() ? 'ml-4' : 'mr-4' }}"
                                 style="background-color: {{ $message->isAdminResponse() ? 'var(--brand-primary-color)' : 'var(--card-background-color)' }}; {{ $message->isAdminResponse() ? 'color: var(--button-text-color);' : '' }}">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-xs font-medium {{ $message->isAdminResponse() ? '' : 'opacity-70' }}">
                                        {{ $message->author_name }}
                                    </span>
                                    <span class="text-xs {{ $message->isAdminResponse() ? 'opacity-70' : 'opacity-50' }}">
                                        {{ $message->created_at_timestamp->format('M j, Y g:i A') }}
                                    </span>
                                </div>
                                <p class="text-sm whitespace-pre-wrap">{{ $message->message_body }}</p>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="p-4 rounded-md text-center mb-4" style="background-color: var(--card-background-color);">
                            <p class="text-sm opacity-60">No messages yet.</p>
                            <p class="text-xs opacity-40 mt-1">Our team will respond to your ticket shortly.</p>
                        </div>
                        @endif
                        
                        {{-- Reply Form (only for non-closed tickets) --}}
                        @if($ticket->ticket_status !== 'ticket_closed')
                        <form action="{{ route('modules.support.reply', $ticket->id) }}" method="POST">
                            @csrf
                            <label class="block text-xs uppercase tracking-wide opacity-50 mb-2">Add Reply</label>
                            <textarea name="message" 
                                      rows="3" 
                                      required
                                      class="w-full px-3 py-2 rounded-md border-0 text-sm mb-2"
                                      style="background-color: var(--card-background-color); color: var(--content-text-color);"
                                      placeholder="Type your message here..."></textarea>
                            <div class="flex justify-end">
                                <button type="submit"
                                        class="px-4 py-2 rounded-md text-sm font-medium"
                                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                    Send Reply
                                </button>
                            </div>
                        </form>
                        @else
                        <div class="p-3 rounded-md text-center text-sm opacity-60" style="background-color: var(--card-background-color);">
                            This ticket is closed. Contact support to reopen if needed.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
    @else
    <div class="rounded-lg p-8 text-center" style="background-color: var(--card-background-color);">
        <p class="text-sm opacity-60">No support tickets yet.</p>
        <p class="text-xs opacity-40 mt-2">Create a ticket if you need assistance.</p>
    </div>
    @endif
</div>
@endsection
