@extends('layouts.platform')

@section('content')
<div x-data="supportTicketsManager()">
    <h1 class="text-2xl font-bold mb-6">{{ __translator('Support Tickets') }}</h1>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    {{-- Search Form --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium mb-2">{{ __translator('Status') }}</label>
                <select id="filter-status" class="ticket-filter w-full px-3 py-2 rounded-md border-0 text-sm"
                        style="background-color: var(--content-background-color); color: var(--content-text-color);">
                    <option value="all">{{ __translator('All Statuses') }}</option>
                    <option value="ticket_open">{{ __translator('New') }}</option>
                    <option value="ticket_in_progress">{{ __translator('Replied') }}</option>
                    <option value="ticket_resolved">{{ __translator('Resolved') }}</option>
                    <option value="ticket_closed">{{ __translator('Closed') }}</option>
                </select>
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-sm font-medium mb-2">{{ __translator('Category') }}</label>
                <select id="filter-category" class="ticket-filter w-full px-3 py-2 rounded-md border-0 text-sm"
                        style="background-color: var(--content-background-color); color: var(--content-text-color);">
                    <option value="all">{{ __translator('All Categories') }}</option>
                    @foreach($categories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sort By Created --}}
            <div>
                <label class="block text-sm font-medium mb-2">{{ __translator('Sort by Created') }}</label>
                <select id="filter-sort" class="ticket-filter w-full px-3 py-2 rounded-md border-0 text-sm"
                        style="background-color: var(--content-background-color); color: var(--content-text-color);">
                    <option value="desc">{{ __translator('Newest First') }}</option>
                    <option value="asc">{{ __translator('Oldest First') }}</option>
                </select>
            </div>
        </div>

        {{-- Keyword Search --}}
        <div>
            <label class="block text-sm font-medium mb-3">
                {{ __translator('Keyword Search') }}
                <span id="ticket-search-spinner" class="ml-2 hidden">
                    <svg class="animate-spin inline" style="color: var(--brand-primary-color); width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </label>
            <input type="text" 
                   id="ticket-keyword"
                   value=""
                   class="w-full md:w-1/2 px-4 py-2 rounded-md border-0"
                   style="background-color: var(--content-background-color); color: var(--content-text-color);"
                   placeholder="{{ __translator('Subject, description, account name, or member...') }}">
        </div>
    </div>

    {{-- Results Count --}}
    <div class="mb-4 text-sm opacity-70">
        <span id="ticket-results-count">{{ __translator('Loading...') }}</span>
    </div>

    {{-- Tickets List (Accordion Style) --}}
    <div id="ticket-results" class="space-y-2">
        <div class="text-center py-8 opacity-60">{{ __translator('Loading tickets...') }}</div>
    </div>

    {{-- Load More --}}
    <div id="ticket-load-more" class="mt-4 text-center hidden">
        <button onclick="loadMore()" 
                class="px-6 py-2 rounded-md text-sm font-medium"
                style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
            {{ __translator('Load More Tickets') }}
        </button>
    </div>
</div>

<script>
// Ticket search state
var searchUrl = '{{ route("admin.support-tickets.search") }}';
var currentPage = 1;
var hasMore = false;
var isLoading = false;
var debounceTimer = null;
var expandedTickets = {}; // Track which tickets are expanded

function getSearchParams(page = 1) {
    const keyword = document.getElementById('ticket-keyword').value.trim();
    const status = document.getElementById('filter-status').value;
    const category = document.getElementById('filter-category').value;
    const sortCreated = document.getElementById('filter-sort').value;
    
    const params = new URLSearchParams();
    params.set('status', status);
    params.set('category', category);
    params.set('sort_created', sortCreated);
    params.set('page', page);
    if (keyword.length > 0) {
        params.set('keyword', keyword);
    }
    return params;
}

function triggerSearch(append = false) {
    if (!append) {
        currentPage = 1;
        expandedTickets = {};
    }
    
    isLoading = true;
    document.getElementById('ticket-search-spinner').classList.remove('hidden');
    
    const params = getSearchParams(currentPage);
    
    fetch(searchUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            hasMore = data.has_more;
            renderResults(data, append);
        })
        .catch(error => {
            console.error('Search error:', error);
        })
        .finally(() => {
            isLoading = false;
            document.getElementById('ticket-search-spinner').classList.add('hidden');
        });
}

function getStatusBadge(status) {
    if (status === 'ticket_open') {
        return '<span class="px-2 py-1 rounded text-xs" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">{{ __translator("New") }}</span>';
    } else if (status === 'ticket_in_progress') {
        return '<span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-warning-color); color: #1a1a2e;">{{ __translator("Replied") }}</span>';
    } else if (status === 'ticket_resolved') {
        return '<span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-success-color); color: white;">{{ __translator("Resolved") }}</span>';
    } else {
        return '<span class="px-2 py-1 rounded text-xs opacity-50" style="background-color: var(--sidebar-hover-background-color);">{{ __translator("Closed") }}</span>';
    }
}

function renderResults(data, append = false) {
    const container = document.getElementById('ticket-results');
    const loadMoreDiv = document.getElementById('ticket-load-more');
    const countSpan = document.getElementById('ticket-results-count');
    
    countSpan.textContent = data.total + ' {{ __translator("tickets found") }}';
    
    if (!append) {
        container.innerHTML = '';
    }
    
    if (data.tickets.length === 0 && !append) {
        container.innerHTML = '<div class="text-center py-8 opacity-60 rounded-lg" style="background-color: var(--card-background-color);">{{ __translator("No support tickets found.") }}</div>';
        loadMoreDiv.classList.add('hidden');
        return;
    }
    
    data.tickets.forEach(function(ticket) {
        const ticketCard = document.createElement('div');
        ticketCard.id = 'ticket-card-' + ticket.id;
        ticketCard.className = 'rounded-lg overflow-hidden';
        ticketCard.style.backgroundColor = 'var(--card-background-color)';
        
        const attachmentBadge = ticket.attachments_count > 0 
            ? `<span class="ml-2 px-2 py-0.5 rounded text-xs" style="background-color: var(--sidebar-hover-background-color);">📎 ${ticket.attachments_count}</span>` 
            : '';
        
        ticketCard.innerHTML = `
            {{-- Ticket Header Row (clickable to expand) --}}
            <div class="ticket-header flex items-center justify-between px-4 py-3 cursor-pointer hover:opacity-90 transition-opacity"
                 onclick="toggleTicket(${ticket.id})"
                 style="border-bottom: 1px solid var(--content-background-color);">
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <svg id="chevron-${ticket.id}" class="w-5 h-5 flex-shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs opacity-50 flex-shrink-0">${escapeHtml(ticket.created_at)}</span>
                    ${getStatusBadge(ticket.status)}
                    <span class="px-2 py-0.5 rounded text-xs flex-shrink-0" style="background-color: var(--sidebar-hover-background-color);">
                        ${escapeHtml(ticket.category || 'N/A')}
                    </span>
                    <span class="text-sm opacity-70 flex-shrink-0">${escapeHtml(ticket.account_name)}</span>
                    <span class="text-sm font-medium truncate">${escapeHtml(ticket.subject)}</span>
                    ${attachmentBadge}
                </div>
            </div>
            
            {{-- Ticket Detail Panel (hidden by default) --}}
            <div id="ticket-detail-${ticket.id}" class="hidden">
                <div class="p-4 text-center text-sm opacity-60">
                    <svg class="animate-spin inline mr-2" style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __translator('Loading details...') }}
                </div>
            </div>
        `;
        container.appendChild(ticketCard);
    });
    
    if (hasMore) {
        loadMoreDiv.classList.remove('hidden');
    } else {
        loadMoreDiv.classList.add('hidden');
    }
}

async function toggleTicket(ticketId) {
    const detailPanel = document.getElementById('ticket-detail-' + ticketId);
    const chevron = document.getElementById('chevron-' + ticketId);
    
    if (expandedTickets[ticketId]) {
        // Collapse
        detailPanel.classList.add('hidden');
        chevron.style.transform = 'rotate(0deg)';
        delete expandedTickets[ticketId];
    } else {
        // Expand
        detailPanel.classList.remove('hidden');
        chevron.style.transform = 'rotate(90deg)';
        expandedTickets[ticketId] = true;
        
        // Fetch details if not already loaded
        if (!detailPanel.dataset.loaded) {
            await loadTicketDetails(ticketId);
            detailPanel.dataset.loaded = 'true';
        }
    }
}

async function loadTicketDetails(ticketId) {
    const detailPanel = document.getElementById('ticket-detail-' + ticketId);
    
    try {
        const response = await fetch(`/administrator/support-tickets/${ticketId}`);
        if (!response.ok) {
            throw new Error('Server returned ' + response.status);
        }
        const data = await response.json();
        
        detailPanel.innerHTML = renderTicketDetail(data);
    } catch (error) {
        console.error('Error loading ticket:', error);
        detailPanel.innerHTML = '<div class="p-4 text-center text-sm" style="color: var(--status-error-color);">{{ __translator("Error loading ticket details") }}: ' + error.message + '</div>';
    }
}

function renderTicketDetail(data) {
    const ticket = data.ticket;
    const attachments = data.attachments || [];
    const messages = data.messages || [];
    const attachmentsHtml = attachments.length > 0 
        ? `<div class="mt-4">
            <label class="block text-xs uppercase tracking-wide opacity-60 mb-2">{{ __translator('Attachments') }} (${attachments.length})</label>
            <div class="flex flex-wrap gap-2">
                ${attachments.map(att => `
                    <a href="${att.url}" 
                       download="${escapeHtml(att.original_filename)}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 rounded text-sm hover:opacity-80 transition-opacity"
                       style="background-color: var(--sidebar-hover-background-color);">
                        <span class="text-sm">${getFileIcon(att.original_filename)}</span>
                        <span class="truncate max-w-[200px]">${escapeHtml(att.original_filename)}</span>
                        <span class="text-xs opacity-50">(${formatFileSize(att.file_size_bytes)})</span>
                    </a>
                `).join('')}
            </div>
           </div>`
        : '';
    
    return `
        <div class="p-4 space-y-4" style="background-color: var(--content-background-color);">
            {{-- Ticket Meta Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="block text-xs uppercase tracking-wide opacity-50 mb-1">{{ __translator('Account') }}</span>
                    <span class="font-medium">${escapeHtml(data.account_name)}</span>
                </div>
                <div>
                    <span class="block text-xs uppercase tracking-wide opacity-50 mb-1">{{ __translator('Created By') }}</span>
                    <span>${escapeHtml(data.created_by_name)}</span>
                    <span class="block text-xs opacity-50">${escapeHtml(data.created_by_email)}</span>
                </div>
                <div>
                    <span class="block text-xs uppercase tracking-wide opacity-50 mb-1">{{ __translator('Status') }}</span>
                    <select onchange="updateTicketStatus(${ticket.id}, this.value)" 
                            class="px-2 py-1 rounded text-sm border-0"
                            style="background-color: var(--card-background-color); color: var(--content-text-color);">
                        <option value="ticket_open" ${ticket.ticket_status === 'ticket_open' ? 'selected' : ''}>{{ __translator('New') }}</option>
                        <option value="ticket_in_progress" ${ticket.ticket_status === 'ticket_in_progress' ? 'selected' : ''}>{{ __translator('Replied') }}</option>
                        <option value="ticket_resolved" ${ticket.ticket_status === 'ticket_resolved' ? 'selected' : ''}>{{ __translator('Resolved') }}</option>
                        <option value="ticket_closed" ${ticket.ticket_status === 'ticket_closed' ? 'selected' : ''}>{{ __translator('Closed') }}</option>
                    </select>
                </div>
                <div>
                    <span class="block text-xs uppercase tracking-wide opacity-50 mb-1">{{ __translator('Assigned To') }}</span>
                    <select onchange="updateTicketAssignment(${ticket.id}, this.value)" 
                            class="px-2 py-1 rounded text-sm border-0"
                            style="background-color: var(--card-background-color); color: var(--content-text-color);">
                        <option value="" ${!data.assigned_to_id ? 'selected' : ''}>{{ __translator('Unassigned') }}</option>
                        ${(data.admins || []).map(admin => `
                            <option value="${admin.id}" ${data.assigned_to_id === admin.id ? 'selected' : ''}>${escapeHtml(admin.name)}</option>
                        `).join('')}
                    </select>
                </div>
            </div>
            
            {{-- Subject --}}
            <div>
                <span class="block text-xs uppercase tracking-wide opacity-50 mb-1">{{ __translator('Subject') }}</span>
                <p class="font-medium">${escapeHtml(ticket.ticket_subject_line)}</p>
            </div>
            
            {{-- Description --}}
            <div class="p-4 rounded-md" style="background-color: var(--card-background-color);">
                <label class="block text-xs uppercase tracking-wide opacity-50 mb-2">{{ __translator('Description') }}</label>
                <div class="text-sm whitespace-pre-wrap">${formatDescription(ticket.ticket_description_body)}</div>
            </div>
            
            ${attachmentsHtml}
            
            {{-- Message Thread --}}
            <div class="border-t pt-4" style="border-color: var(--sidebar-hover-background-color);">
                <label class="block text-xs uppercase tracking-wide opacity-50 mb-2">{{ __translator('Conversation') }}</label>
                ${messages.length > 0 ? `
                    <div class="space-y-3 mb-4">
                        ${messages.map(msg => `
                            <div class="p-3 rounded-md ${msg.message_type === 'admin_response' ? 'ml-4' : 'mr-4'}"
                                 style="background-color: ${msg.message_type === 'admin_response' ? 'var(--brand-primary-color)' : 'var(--card-background-color)'}; ${msg.message_type === 'admin_response' ? 'color: var(--button-text-color);' : ''}">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-xs font-medium ${msg.message_type === 'admin_response' ? '' : 'opacity-70'}">
                                        ${escapeHtml(msg.author_name)}
                                    </span>
                                    <span class="text-xs ${msg.message_type === 'admin_response' ? 'opacity-70' : 'opacity-50'}">
                                        ${escapeHtml(msg.created_at)}
                                    </span>
                                </div>
                                <p class="text-sm whitespace-pre-wrap">${escapeHtml(msg.message_body)}</p>
                            </div>
                        `).join('')}
                    </div>
                ` : `
                    <div class="p-4 rounded-md text-center mb-4" style="background-color: var(--card-background-color);">
                        <p class="text-sm opacity-60">{{ __translator('No messages yet.') }}</p>
                    </div>
                `}
                
                {{-- Admin Response Form --}}
                <label class="block text-xs uppercase tracking-wide opacity-50 mb-2">{{ __translator('Add Response') }}</label>
                <textarea id="response-${ticket.id}"
                          rows="3"
                          class="w-full px-3 py-2 rounded-md border-0 text-sm"
                          style="background-color: var(--card-background-color); color: var(--content-text-color);"
                          placeholder="{{ __translator('Type your response here...') }}"></textarea>
                <div class="flex justify-end mt-2">
                    <button onclick="submitTicketResponse(${ticket.id})"
                            id="submit-btn-${ticket.id}"
                            class="px-4 py-2 rounded-md text-sm font-medium"
                            style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                        {{ __translator('Send Response') }}
                    </button>
                </div>
            </div>
        </div>
    `;
}

async function updateTicketStatus(ticketId, newStatus) {
    try {
        const response = await fetch(`/administrator/support-tickets/${ticketId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: newStatus })
        });
        const data = await response.json();
        if (data.success) {
            // Refresh the ticket list to show updated status
            triggerSearch(false);
        }
    } catch (error) {
        console.error('Error updating status:', error);
        alert('{{ __translator("Error updating status") }}');
    }
}

async function updateTicketAssignment(ticketId, adminId) {
    try {
        const response = await fetch(`/administrator/support-tickets/${ticketId}/assign`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ admin_id: adminId || null })
        });
        const data = await response.json();
        if (!data.success) {
            alert('{{ __translator("Error updating assignment") }}');
        }
    } catch (error) {
        console.error('Error updating assignment:', error);
        alert('{{ __translator("Error updating assignment") }}');
    }
}

async function submitTicketResponse(ticketId) {
    const textarea = document.getElementById('response-' + ticketId);
    const btn = document.getElementById('submit-btn-' + ticketId);
    const responseText = textarea.value.trim();
    
    if (!responseText) return;
    
    btn.disabled = true;
    btn.textContent = '{{ __translator("Sending...") }}';
    
    try {
        const response = await fetch(`/administrator/support-tickets/${ticketId}/respond`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ response: responseText })
        });
        const data = await response.json();
        if (data.success) {
            textarea.value = '';
            // Refresh ticket list
            triggerSearch(false);
        }
    } catch (error) {
        console.error('Error submitting response:', error);
        alert('{{ __translator("Error submitting response") }}');
    } finally {
        btn.disabled = false;
        btn.textContent = '{{ __translator("Send Response") }}';
    }
}

function loadMore() {
    if (isLoading || !hasMore) return;
    currentPage++;
    triggerSearch(true);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDescription(text) {
    if (!text) return '';
    return escapeHtml(text).replace(/\n/g, '<br>');
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function getFileIcon(filename) {
    if (!filename) return '📎';
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
        'pdf': '📄',
        'doc': '📝', 'docx': '📝',
        'txt': '📃',
        'zip': '📦',
        'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️'
    };
    return icons[ext] || '📎';
}

// Event listeners
document.getElementById('ticket-keyword').addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        triggerSearch(false);
    }, 300);
});

document.querySelectorAll('.ticket-filter').forEach(function(el) {
    el.addEventListener('change', function() {
        clearTimeout(debounceTimer);
        triggerSearch(false);
    });
});

// Simplified Alpine data (no longer needs modal logic)
function supportTicketsManager() {
    return {};
}

// Load first 20 tickets on page load
triggerSearch(false);
</script>
@endsection
