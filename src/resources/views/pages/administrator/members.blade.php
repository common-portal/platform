@extends('layouts.platform')

@section('content')
{{-- Admin Members Search Page --}}

<div class="max-w-6xl mx-auto" x-data="{ 
    showProfileModal: false, 
    selectedMember: null,
    originalRole: null,
    editingEmail: false,
    editingRole: false,
    openProfile(member) {
        this.selectedMember = member;
        this.originalRole = member.is_admin;
        this.editingEmail = false;
        this.editingRole = false;
        this.showProfileModal = true;
    },
    saveRole() {
        const newRole = this.selectedMember.is_admin;
        const confirmMsg = newRole 
            ? '{{ __translator("You are about to grant Administrator privileges to this member. Are you sure?") }}'
            : '{{ __translator("You are about to revoke Administrator privileges from this member. Are you sure?") }}';
        
        if (!confirm(confirmMsg)) {
            this.selectedMember.is_admin = this.originalRole;
            return;
        }
        
        fetch('{{ url("/administrator/members") }}/' + this.selectedMember.id + '/toggle-admin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(response => {
            if (response.ok) {
                this.originalRole = newRole;
                this.editingRole = false;
                alert('{{ __translator("Role updated successfully.") }}');
            } else {
                this.selectedMember.is_admin = this.originalRole;
                this.editingRole = false;
                alert('{{ __translator("Failed to update role.") }}');
            }
        }).catch(() => {
            this.selectedMember.is_admin = this.originalRole;
            this.editingRole = false;
            alert('{{ __translator("Failed to update role.") }}');
        });
    },
    confirmRoleChange(event) {
        const newRole = event.target.value === 'true';
        const confirmMsg = newRole 
            ? '{{ __translator("You are about to grant Administrator privileges to this member. Are you sure?") }}'
            : '{{ __translator("You are about to revoke Administrator privileges from this member. Are you sure?") }}';
        
        if (!confirm(confirmMsg)) {
            this.selectedMember.is_admin = this.originalRole;
            return;
        }
        
        // Submit the role change
        fetch('{{ url("/administrator/members") }}/' + this.selectedMember.id + '/toggle-admin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(response => {
            if (response.ok) {
                this.originalRole = newRole;
                alert('{{ __translator("Role updated successfully.") }}');
            } else {
                this.selectedMember.is_admin = this.originalRole;
                alert('{{ __translator("Failed to update role.") }}');
            }
        }).catch(() => {
            this.selectedMember.is_admin = this.originalRole;
            alert('{{ __translator("Failed to update role.") }}');
        });
    },
    saveEmail() {
        if (!confirm('{{ __translator("Update login email address for this member?") }}')) {
            this.selectedMember.email = this.selectedMember.original_email;
            return;
        }
        
        fetch('{{ url("/administrator/members") }}/' + this.selectedMember.id + '/update-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: this.selectedMember.email })
        }).then(response => {
            if (response.ok) {
                this.selectedMember.original_email = this.selectedMember.email;
                this.editingEmail = false;
                alert('{{ __translator("Email updated successfully.") }}');
            } else {
                this.selectedMember.email = this.selectedMember.original_email;
                this.editingEmail = false;
                alert('{{ __translator("Failed to update email.") }}');
            }
        }).catch(() => {
            this.selectedMember.email = this.selectedMember.original_email;
            this.editingEmail = false;
            alert('{{ __translator("Failed to update email.") }}');
        });
    }
}">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ __translator('Member Search') }}</h1>
        <a href="{{ route('admin.index') }}" class="text-sm opacity-70 hover:opacity-100">← {{ __translator('Back to Admin') }}</a>
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

    {{-- Search Form --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            {{-- Verification Status --}}
            <div>
                <label class="block text-sm font-medium mb-3">{{ __translator('Verification Status') }}</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="verified" value="all" checked class="mr-2 member-filter">
                        <span class="text-sm">{{ __translator('All') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="verified" value="verified" class="mr-2 member-filter">
                        <span class="text-sm">{{ __translator('Verified Only') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="verified" value="unverified" class="mr-2 member-filter">
                        <span class="text-sm">{{ __translator('Unverified Only') }}</span>
                    </label>
                </div>
            </div>

            {{-- Role --}}
            <div>
                <label class="block text-sm font-medium mb-3">{{ __translator('Role') }}</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="role" value="all" checked class="mr-2 member-filter">
                        <span class="text-sm">{{ __translator('All') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="role" value="admin" class="mr-2 member-filter">
                        <span class="text-sm">{{ __translator('Administrators Only') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="role" value="member" class="mr-2 member-filter">
                        <span class="text-sm">{{ __translator('Regular Members Only') }}</span>
                    </label>
                </div>
            </div>

            {{-- Sort By Created --}}
            <div>
                <label class="block text-sm font-medium mb-3">{{ __translator('Sort by Created') }}</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="sort_created" value="desc" checked class="mr-2 member-filter">
                        <span class="text-sm">{{ __translator('Newest First') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="sort_created" value="asc" class="mr-2 member-filter">
                        <span class="text-sm">{{ __translator('Oldest First') }}</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Keyword Search --}}
        <div>
            <label class="block text-sm font-medium mb-3">
                {{ __translator('Keyword Search') }}
                <span id="member-search-spinner" class="ml-2 hidden">
                    <svg class="animate-spin inline" style="color: var(--brand-primary-color); width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </label>
            <input type="text" 
                   id="member-keyword"
                   value=""
                   class="w-full md:w-1/2 px-4 py-2 rounded-md border-0"
                   style="background-color: var(--content-background-color); color: var(--content-text-color);"
                   placeholder="{{ __translator('Name or email address...') }}">
        </div>
    </div>

    {{-- Results Container (populated via JS on page load) --}}
    <div id="member-results-container">
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="opacity-70">{{ __translator('Loading members...') }}</p>
        </div>
    </div>

    {{-- Member Profile Modal --}}
    <div x-show="showProfileModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background-color: rgba(0,0,0,0.7);">
        <div @click.away="showProfileModal = false" 
             class="w-full rounded-lg p-6"
             style="max-width: 80%; background-color: var(--card-background-color);">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">{{ __translator('Member Profile') }}</h2>
                <button @click="showProfileModal = false" class="opacity-60 hover:opacity-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedMember">
                <div class="space-y-4">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl mr-4" 
                             style="background-color: var(--sidebar-hover-background-color);">
                            <span x-text="(selectedMember.first_name || selectedMember.email).charAt(0).toUpperCase()"></span>
                        </div>
                        <div>
                            <p class="text-lg font-medium" x-text="selectedMember.first_name + ' ' + selectedMember.last_name || selectedMember.email"></p>
                            <p class="text-sm opacity-60" x-text="selectedMember.email"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="opacity-60 mb-1">{{ __translator('First Name') }}</p>
                            <p class="font-medium" x-text="selectedMember.first_name || '—'"></p>
                        </div>
                        <div>
                            <p class="opacity-60 mb-1">{{ __translator('Last Name') }}</p>
                            <p class="font-medium" x-text="selectedMember.last_name || '—'"></p>
                        </div>
                        <div class="col-span-2">
                            <p class="opacity-60 mb-1">{{ __translator('Login Email') }}</p>
                            <div class="flex gap-2 items-center">
                                <input type="email" 
                                       x-model="selectedMember.email"
                                       :disabled="!editingEmail"
                                       :class="editingEmail ? '' : 'opacity-60 cursor-not-allowed'"
                                       class="flex-1 px-3 py-2 rounded-md border-0 text-sm"
                                       style="background-color: var(--content-background-color); color: var(--content-text-color);">
                                <button type="button"
                                        x-show="!editingEmail"
                                        @click="editingEmail = true"
                                        class="p-2 rounded-md hover:opacity-80"
                                        style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);"
                                        title="{{ __translator('Edit') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button type="button"
                                        x-show="editingEmail"
                                        @click="saveEmail()"
                                        class="px-3 py-2 rounded-md text-sm"
                                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                    {{ __translator('Save') }}
                                </button>
                                <button type="button"
                                        x-show="editingEmail"
                                        @click="editingEmail = false; selectedMember.email = selectedMember.original_email"
                                        class="px-3 py-2 rounded-md text-sm"
                                        style="background-color: var(--sidebar-hover-background-color);">
                                    {{ __translator('Cancel') }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="opacity-60 mb-1">{{ __translator('Preferred Language') }}</p>
                            <p class="font-medium" x-text="selectedMember.language"></p>
                        </div>
                        <div>
                            <p class="opacity-60 mb-1">{{ __translator('Verification') }}</p>
                            <p class="font-medium">
                                <span x-show="selectedMember.verified" class="px-2 py-1 rounded text-xs" style="background-color: var(--status-success-color); color: white;">{{ __translator('Verified') }}</span>
                                <span x-show="!selectedMember.verified" class="px-2 py-1 rounded text-xs" style="background-color: var(--status-warning-color); color: #1a1a2e;">{{ __translator('Unverified') }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="opacity-60 mb-1">{{ __translator('Role') }}</p>
                            <div class="flex gap-2 items-center">
                                <select x-model="selectedMember.is_admin"
                                        :disabled="!editingRole || selectedMember.is_self"
                                        :class="editingRole ? '' : 'opacity-60 cursor-not-allowed'"
                                        class="flex-1 px-3 py-2 rounded-md text-sm border-0"
                                        style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);">
                                    <option :value="false">{{ __translator('Member') }}</option>
                                    <option :value="true">{{ __translator('Administrator') }}</option>
                                </select>
                                <button type="button"
                                        x-show="!editingRole && !selectedMember.is_self"
                                        @click="editingRole = true"
                                        class="p-2 rounded-md hover:opacity-80"
                                        style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);"
                                        title="{{ __translator('Edit') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button type="button"
                                        x-show="editingRole"
                                        @click="saveRole()"
                                        class="px-3 py-2 rounded-md text-sm"
                                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                    {{ __translator('Save') }}
                                </button>
                                <button type="button"
                                        x-show="editingRole"
                                        @click="editingRole = false; selectedMember.is_admin = originalRole"
                                        class="px-3 py-2 rounded-md text-sm"
                                        style="background-color: var(--sidebar-hover-background-color);">
                                    {{ __translator('Cancel') }}
                                </button>
                            </div>
                            <p x-show="selectedMember.is_self" class="text-xs opacity-60 mt-1">{{ __translator('Cannot change own role') }}</p>
                        </div>
                        <div>
                            <p class="opacity-60 mb-1">{{ __translator('Joined') }}</p>
                            <p class="font-medium" x-text="selectedMember.joined"></p>
                        </div>
                        <div x-show="selectedMember.verified">
                            <p class="opacity-60 mb-1">{{ __translator('Verified At') }}</p>
                            <p class="font-medium" x-text="selectedMember.verified_at"></p>
                        </div>
                    </div>

                    <div class="pt-4 border-t mt-6" style="border-color: var(--sidebar-hover-background-color);">
                        <p class="text-xs opacity-60">{{ __translator('Member Hash') }}: <span x-text="selectedMember.hash"></span></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

@push('scripts')
<script>
(function() {
    let debounceTimer;
    let currentPage = 1;
    let hasMore = false;
    let isLoading = false;
    let totalResults = 0;
    
    const debounceDelay = 300;
    const searchUrl = '{{ route("admin.members.search") }}';
    const csrfToken = '{{ csrf_token() }}';
    
    const translations = {
        found: '{{ __translator("Found") }}',
        members: '{{ __translator("member(s)") }}',
        noResults: '{{ __translator("No members found matching your search criteria.") }}',
        verified: '{{ __translator("Verified") }}',
        unverified: '{{ __translator("Unverified") }}',
        admin: '{{ __translator("Admin") }}',
        member: '{{ __translator("Member") }}',
        profile: '{{ __translator("Profile") }}',
        loadMore: '{{ __translator("Load More") }}',
        loading: '{{ __translator("Loading...") }}'
    };
    
    function getSearchParams(page = 1) {
        const keyword = document.getElementById('member-keyword').value.trim();
        const verified = document.querySelector('input[name="verified"]:checked')?.value || 'all';
        const role = document.querySelector('input[name="role"]:checked')?.value || 'all';
        const sortCreated = document.querySelector('input[name="sort_created"]:checked')?.value || 'desc';
        
        const params = new URLSearchParams();
        params.set('verified', verified);
        params.set('role', role);
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
        document.getElementById('member-search-spinner').classList.remove('hidden');
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
            document.getElementById('member-search-spinner').classList.add('hidden');
            updateLoadMoreButton();
        });
    }
    
    function loadMore() {
        if (isLoading || !hasMore) return;
        currentPage++;
        triggerSearch(true);
    }
    
    function updateLoadMoreButton() {
        const btn = document.getElementById('member-load-more');
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
    
    function renderMemberRow(member) {
        const verifiedBadge = member.is_verified 
            ? `<span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-success-color); color: white;">${translations.verified}</span>`
            : `<span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-warning-color); color: #1a1a2e;">${translations.unverified}</span>`;
        
        const roleBadge = member.is_admin
            ? `<span class="px-2 py-1 rounded text-xs" style="background-color: var(--status-error-color); color: white;">${translations.admin}</span>`
            : `<span class="px-2 py-1 rounded text-xs opacity-60">${translations.member}</span>`;
        
        return `
            <tr class="border-b" style="border-color: var(--sidebar-hover-background-color);">
                <td class="py-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium mr-3" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                            ${member.initial}
                        </div>
                        <div>
                            <div class="font-medium">${member.full_name}</div>
                            <div class="text-sm opacity-60">${member.email}</div>
                        </div>
                    </div>
                </td>
                <td class="py-4 text-sm opacity-70">${member.joined}</td>
                <td class="py-4">${verifiedBadge}</td>
                <td class="py-4">${roleBadge}</td>
                <td class="py-4 text-right">
                    <button type="button"
                            onclick="openMemberProfile(${JSON.stringify(member).replace(/"/g, '&quot;')})"
                            class="px-3 py-1 rounded text-sm"
                            style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                        ${translations.profile}
                    </button>
                </td>
            </tr>
        `;
    }
    
    function renderResults(data, append = false) {
        const container = document.getElementById('member-results-container');
        
        if (data.members.length === 0 && !append) {
            container.innerHTML = `
                <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
                    <p class="opacity-70">${translations.noResults}</p>
                </div>
            `;
            return;
        }
        
        if (append) {
            // Append to existing tbody
            const tbody = document.getElementById('member-results-tbody');
            if (tbody) {
                data.members.forEach(member => {
                    tbody.insertAdjacentHTML('beforeend', renderMemberRow(member));
                });
            }
            updateLoadMoreButton();
            return;
        }
        
        // Build fresh table
        let rowsHtml = '';
        data.members.forEach(member => {
            rowsHtml += renderMemberRow(member);
        });
        
        const html = `
            <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
                <p class="text-sm opacity-70 mb-4">${translations.found} ${data.total} ${translations.members}</p>
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm opacity-60 border-b" style="border-color: var(--sidebar-hover-background-color);">
                            <th class="pb-3">Member</th>
                            <th class="pb-3">Joined</th>
                            <th class="pb-3">Verified</th>
                            <th class="pb-3">Role</th>
                            <th class="pb-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="member-results-tbody">
                        ${rowsHtml}
                    </tbody>
                </table>
                <div class="mt-4 text-center">
                    <button type="button" 
                            id="member-load-more"
                            onclick="window.loadMoreMembers()"
                            class="px-6 py-2 rounded-md text-sm font-medium"
                            style="background-color: var(--sidebar-hover-background-color); display: ${data.has_more ? 'inline-block' : 'none'};">
                        ${translations.loadMore}
                    </button>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    // Global functions
    window.loadMoreMembers = loadMore;
    
    window.openMemberProfile = function(member) {
        const alpineComponent = document.querySelector('[x-data]').__x.$data;
        alpineComponent.selectedMember = {
            id: member.id,
            hash: member.hash,
            first_name: member.first_name,
            last_name: member.last_name,
            email: member.email,
            original_email: member.email,
            verified: member.is_verified,
            verified_at: member.verified_at,
            is_admin: member.is_admin,
            language: member.language,
            joined: member.joined_full,
            is_self: member.is_self
        };
        alpineComponent.originalRole = member.is_admin;
        alpineComponent.showProfileModal = true;
    };
    
    // Debounced keyword input - realtime search as user types
    document.getElementById('member-keyword').addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => triggerSearch(false), debounceDelay);
    });
    
    // Immediate search on filter change (resets to page 1)
    document.querySelectorAll('.member-filter').forEach(function(el) {
        el.addEventListener('change', function() {
            clearTimeout(debounceTimer);
            triggerSearch(false);
        });
    });
    
    // Load first 20 members on page load
    triggerSearch(false);
})();
</script>
@endpush
@endsection
