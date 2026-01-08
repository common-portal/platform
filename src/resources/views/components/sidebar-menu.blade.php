{{-- Sidebar Menu Component --}}
{{-- Reference: COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md → Sidebar Menu Structure --}}

<div class="space-y-2">

    {{-- Administrator Link (conditional) --}}
    @if(auth()->check() && auth()->user()->is_platform_administrator)
    <a href="/administrator" 
       class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors"
       style="background-color: var(--status-error-color); color: white;">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Administrator
    </a>
    <div class="my-4 border-t" style="border-color: var(--sidebar-hover-background-color);"></div>
    @endif

    {{-- Account Selector --}}
    @auth
    <div class="mb-4">
        <label class="block text-xs uppercase tracking-wide opacity-60 mb-2">Active Account</label>
        <select id="account-selector" 
                onchange="switchAccount(this.value)"
                class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color); focus-ring-color: var(--brand-primary-color);">
            @foreach($userAccounts ?? [] as $account)
            <option value="{{ $account->id }}" {{ ($activeAccountId ?? null) == $account->id ? 'selected' : '' }}>
                {{ $account->account_display_name }}
            </option>
            @endforeach
        </select>
    </div>

    {{-- Add Business Account Link --}}
    <a href="/account/create" 
       class="flex items-center px-3 py-2 rounded-md text-sm transition-colors hover:opacity-80"
       style="color: var(--brand-primary-color);">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add Business Account
    </a>

    <div class="my-4 border-t" style="border-color: var(--sidebar-hover-background-color);"></div>
    @endauth

    {{-- Account Menu Items --}}
    @auth
    <div class="space-y-1">
        
        {{-- Account Settings --}}
        @if($canAccessAccountSettings ?? true)
        <a href="/account/settings" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Account
        </a>
        @endif

        {{-- Dashboard --}}
        @if($canAccessAccountDashboard ?? true)
        <a href="/account/dashboard" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            Dashboard
        </a>
        @endif

        {{-- Team --}}
        @if($canManageTeamMembers ?? true)
        <a href="/account/team" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Team
        </a>
        @endif

        {{-- Developer (optional module) --}}
        @if($canAccessDeveloperTools ?? false)
        <a href="/modules/developer" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            Developer
        </a>
        @endif

        {{-- Support (optional module) --}}
        @if($canAccessSupportTickets ?? false)
        <a href="/modules/support" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Support
        </a>
        @endif

        {{-- Transactions (optional module) --}}
        @if($canViewTransactionHistory ?? false)
        <a href="/modules/transactions" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            Transactions
        </a>
        @endif

        {{-- Billing (optional module) --}}
        @if($canViewBillingHistory ?? false)
        <a href="/modules/billing" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Billing
        </a>
        @endif

    </div>

    <div class="my-4 border-t" style="border-color: var(--sidebar-hover-background-color);"></div>

    {{-- Member Profile --}}
    <a href="/member/settings" 
       class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
       style="color: var(--sidebar-text-color);">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        My Profile
    </a>

    {{-- Exit / Logout --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-2">
        @csrf
        <button type="submit" 
                class="sidebar-menu-item w-full flex items-center px-3 py-2 rounded-md text-sm transition-colors text-left"
                style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Exit
        </button>
    </form>
    @endauth

    {{-- Guest Links --}}
    @guest
    <a href="/login-register" 
       class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors"
       style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
        </svg>
        Login / Register
    </a>
    @endguest

</div>

{{-- Account Switcher Script --}}
<script>
    function switchAccount(accountId) {
        window.location.href = '/account/switch/' + accountId;
    }
</script>

<style>
    .sidebar-menu-item:hover {
        background-color: var(--sidebar-hover-background-color);
    }
</style>
