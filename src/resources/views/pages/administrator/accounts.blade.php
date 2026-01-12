@extends('layouts.platform')

@section('content')
{{-- Admin Accounts Search Page --}}

<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ __translator('Account Search') }}</h1>
        <a href="{{ route('admin.index') }}" class="text-sm opacity-70 hover:opacity-100">← {{ __translator('Back to Admin') }}</a>
    </div>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    {{-- Search Form --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            {{-- Verification Status --}}
            <div>
                <label class="block text-sm font-medium mb-3">{{ __translator('Verification Status') }}</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="verified" value="all" checked class="mr-2 account-filter">
                        <span class="text-sm">{{ __translator('All') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="verified" value="verified" class="mr-2 account-filter">
                        <span class="text-sm">{{ __translator('Verified Only') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="verified" value="unverified" class="mr-2 account-filter">
                        <span class="text-sm">{{ __translator('Unverified Only') }}</span>
                    </label>
                </div>
            </div>

            {{-- Account Type --}}
            <div>
                <label class="block text-sm font-medium mb-3">{{ __translator('Account Type') }}</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="type" value="all" checked class="mr-2 account-filter">
                        <span class="text-sm">{{ __translator('All') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="type" value="personal_individual" class="mr-2 account-filter">
                        <span class="text-sm">{{ __translator('Personal / Individual') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="type" value="business_organization" class="mr-2 account-filter">
                        <span class="text-sm">{{ __translator('Business / Organization') }}</span>
                    </label>
                </div>
            </div>

            {{-- Sort By Created --}}
            <div>
                <label class="block text-sm font-medium mb-3">{{ __translator('Sort by Created') }}</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="sort_created" value="desc" checked class="mr-2 account-filter">
                        <span class="text-sm">{{ __translator('Newest First') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="sort_created" value="asc" class="mr-2 account-filter">
                        <span class="text-sm">{{ __translator('Oldest First') }}</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Keyword Search --}}
        <div>
            <label class="block text-sm font-medium mb-3">
                {{ __translator('Keyword Search') }}
                <span id="account-search-spinner" class="ml-2 hidden">
                    <svg class="animate-spin inline" style="color: var(--brand-primary-color); width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </label>
            <input type="text" 
                   id="account-keyword"
                   value=""
                   class="w-full md:w-1/2 px-4 py-2 rounded-md border-0"
                   style="background-color: var(--content-background-color); color: var(--content-text-color);"
                   placeholder="{{ __translator('Account name, owner name, or email...') }}">
        </div>
    </div>

    {{-- Results Container (populated via JS on page load) --}}
    <div id="account-results-container">
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="opacity-70">{{ __translator('Loading accounts...') }}</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    let debounceTimer;
    let currentPage = 1;
    let hasMore = false;
    let isLoading = false;
    let totalResults = 0;
    
    const debounceDelay = 300;
    const searchUrl = '{{ route("admin.accounts.search") }}';
    const impersonateUrl = '{{ url("/administrator/accounts") }}';
    const csrfToken = '{{ csrf_token() }}';
    
    const translations = {
        found: '{{ __translator("Found") }}',
        accounts: '{{ __translator("account(s)") }}',
        noResults: '{{ __translator("No accounts found matching your search criteria.") }}',
        verified: '{{ __translator("Verified") }}',
        unverified: '{{ __translator("Unverified") }}',
        personal: '{{ __translator("Personal") }}',
        business: '{{ __translator("Business") }}',
        impersonate: '{{ __translator("Impersonate") }}',
        loadMore: '{{ __translator("Load More") }}',
        loading: '{{ __translator("Loading...") }}'
    };
    
    function getSearchParams(page = 1) {
        const keyword = document.getElementById('account-keyword').value.trim();
        const verified = document.querySelector('input[name="verified"]:checked')?.value || 'all';
        const type = document.querySelector('input[name="type"]:checked')?.value || 'all';
        const sortCreated = document.querySelector('input[name="sort_created"]:checked')?.value || 'desc';
        
        const params = new URLSearchParams();
        params.set('verified', verified);
        params.set('type', type);
        params.set('sort_created', sortCreated);
        params.set('page', page);
        if (keyword.length > 0) {
            params.set('keyword', keyword);
        }
        return params;
    }
    
    function triggerSearch(append = false) {
        // Reset page if new search
        if (!append) {
            currentPage = 1;
        }
        
        isLoading = true;
        document.getElementById('account-search-spinner').classList.remove('hidden');
        updateLoadMoreButton();
        
        const params = getSearchParams(currentPage);
        
        fetch(searchUrl + '?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            hasMore = data.has_more;
            totalResults = data.total;
            renderResults(data, append);
        })
        .catch(error => {
            console.error('Search error:', error);
        })
        .finally(() => {
            isLoading = false;
            document.getElementById('account-search-spinner').classList.add('hidden');
            updateLoadMoreButton();
        });
    }
    
    function loadMore() {
        if (isLoading || !hasMore) return;
        currentPage++;
        triggerSearch(true);
    }
    
    function updateLoadMoreButton() {
        const btn = document.getElementById('account-load-more');
        if (!btn) return;
        
        if (isLoading) {
            btn.textContent = translations.loading;
            btn.disabled = true;
        } else {
            btn.textContent = translations.loadMore;
            btn.disabled = false;
        }
        
        btn.style.display = hasMore ? 'block' : 'none';
    }
    
    function renderAccountRow(account) {
        const typeBadge = account.type === 'personal_individual'
            ? `<span class="px-2 py-1 rounded text-xs" style="background-color: var(--sidebar-hover-background-color);">${translations.personal}</span>`
            : `<span class="px-2 py-1 rounded text-xs" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">${translations.business}</span>`;
        
        const verifiedBadge = account.is_verified 
            ? `<span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-success-color); color: white;">${translations.verified}</span>`
            : `<span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-warning-color); color: #1a1a2e;">${translations.unverified}</span>`;
        
        return `
            <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                <td class="py-4">
                    <div class="font-medium">${account.name}</div>
                    <div class="text-sm opacity-60">${account.email || ''}</div>
                </td>
                <td class="py-4">${typeBadge}</td>
                <td class="py-4">
                    <div class="font-medium">${account.owner_name || '-'}</div>
                    <div class="text-sm opacity-60">${account.owner_email || ''}</div>
                </td>
                <td class="py-4">${verifiedBadge}</td>
                <td class="py-4 text-sm opacity-70">${account.created}</td>
                <td class="py-4 text-right">
                    <form method="POST" action="${impersonateUrl}/${account.id}/impersonate" class="inline" onsubmit="handleImpersonateSubmit(this)">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <button type="submit" class="impersonate-btn px-3 py-1 rounded text-sm" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                            ${translations.impersonate}
                        </button>
                    </form>
                </td>
            </tr>
        `;
    }
    
    function renderResults(data, append = false) {
        const container = document.getElementById('account-results-container');
        
        if (data.accounts.length === 0 && !append) {
            container.innerHTML = `
                <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
                    <p class="opacity-70">${translations.noResults}</p>
                </div>
            `;
            return;
        }
        
        if (append) {
            // Append to existing tbody
            const tbody = document.getElementById('account-results-tbody');
            if (tbody) {
                data.accounts.forEach(account => {
                    tbody.insertAdjacentHTML('beforeend', renderAccountRow(account));
                });
            }
            updateLoadMoreButton();
            return;
        }
        
        // Build fresh table
        let rowsHtml = '';
        data.accounts.forEach(account => {
            rowsHtml += renderAccountRow(account);
        });
        
        const html = `
            <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                <p class="text-sm opacity-70 mb-4">${translations.found} ${data.total} ${translations.accounts}</p>
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm opacity-60 border-b" style="border-color: var(--sidebar-hover-background-color);">
                            <th class="pb-3">Account</th>
                            <th class="pb-3">Type</th>
                            <th class="pb-3">Owner</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Created</th>
                            <th class="pb-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="account-results-tbody">
                        ${rowsHtml}
                    </tbody>
                </table>
                <div class="mt-4 text-center">
                    <button type="button" 
                            id="account-load-more"
                            onclick="window.loadMoreAccounts()"
                            class="px-6 py-2 rounded-md text-sm font-medium"
                            style="background-color: var(--sidebar-hover-background-color); display: ${data.has_more ? 'inline-block' : 'none'};">
                        ${translations.loadMore}
                    </button>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    // Global function for load more
    window.loadMoreAccounts = loadMore;
    
    // Handle Impersonate button submit - disable and show spinner
    window.handleImpersonateSubmit = function(form) {
        const btn = form.querySelector('button[type="submit"]');
        
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.innerHTML = '<svg style="animation: spin 1s linear infinite; height: 16px; width: 16px; margin: 0 auto;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        
        return true; // Allow form submission
    };
    
    // Debounced keyword input - realtime search as user types
    document.getElementById('account-keyword').addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => triggerSearch(false), debounceDelay);
    });
    
    // Immediate search on filter change (resets to page 1)
    document.querySelectorAll('.account-filter').forEach(function(el) {
        el.addEventListener('change', function() {
            clearTimeout(debounceTimer);
            triggerSearch(false);
        });
    });
    
    // Load first 20 accounts on page load
    triggerSearch(false);
})();
</script>
@endpush
@endsection
