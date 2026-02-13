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
        {{ __translator('Administrator') }}
    </a>
    <div class="my-4 border-t" style="border-color: var(--sidebar-hover-background-color);"></div>
    @endif

    {{-- Account Selector --}}
    @auth
    @php
        $isImpersonating = session('admin_impersonating_from') !== null;
        $impersonatedAccountId = $isImpersonating ? session('active_account_id') : null;
        $impersonatedAccount = $isImpersonating ? \App\Models\TenantAccount::find($impersonatedAccountId) : null;
    @endphp
    <div class="mb-4">
        <label class="block text-xs uppercase tracking-wide opacity-60 mb-2">
            {{ __translator('Active Account') }}
        </label>
        <select id="account-selector" 
                onchange="switchAccount(this.value)"
                class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
                style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color); focus-ring-color: var(--brand-primary-color);">
            @if($isImpersonating && $impersonatedAccount)
            {{-- Show impersonated account first with indicator --}}
            <option value="{{ $impersonatedAccount->id }}" selected>
                ⚡ {{ $impersonatedAccount->account_display_name }}
            </option>
            <option disabled>──────────</option>
            @endif
            {{-- Show admin's normal accounts --}}
            @foreach($userAccounts ?? [] as $account)
            @if(!$isImpersonating || $account->id != $impersonatedAccountId)
            <option value="{{ $account->id }}" {{ !$isImpersonating && ($activeAccountId ?? null) == $account->id ? 'selected' : '' }}>
                {{ $account->account_display_name }}
            </option>
            @endif
            @endforeach
        </select>
        @if($isImpersonating)
        <p class="text-xs opacity-60 mt-1">{{ __translator('Select your account to exit admin view') }}</p>
        @endif
    </div>

    {{-- Add Business Account Link (hidden during impersonation) --}}
    @if(!$isImpersonating)
    <a href="/account/create" 
       class="flex items-center px-3 py-2 rounded-md text-sm transition-colors hover:opacity-80"
       style="color: var(--brand-primary-color);">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __translator('Add Business Account') }}
    </a>
    @endif

    <div class="my-4 border-t" style="border-color: var(--sidebar-hover-background-color);"></div>
    @endauth

    {{-- Account Menu Items --}}
    @auth
    <div class="space-y-1">
        
        {{-- Account Settings --}}
        @php
            $currentAccount = \App\Models\TenantAccount::find(session('active_account_id'));
            $isAccountSettingsEnabled = $menuItemEnabled['account_settings'] ?? true;
            $hasAccountSettingsAccess = $canAccessAccountSettings ?? true;
        @endphp
        @if($isAccountSettingsEnabled && $hasAccountSettingsAccess)
        <a href="/account/settings" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('account/settings') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            @if($currentAccount && $currentAccount->branding_logo_image_path)
            <img src="{{ asset('storage/' . $currentAccount->branding_logo_image_path) }}" 
                 alt="{{ $currentAccount->account_display_name }}" 
                 class="w-5 h-5 mr-3 rounded object-cover">
            @else
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            @endif
            {{ __translator('Account') }}
        </a>
        @elseif($isAccountSettingsEnabled)
        {{-- Enabled but no permission - show disabled --}}
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                @if($currentAccount && $currentAccount->branding_logo_image_path)
                <img src="{{ asset('storage/' . $currentAccount->branding_logo_image_path) }}" 
                     alt="{{ $currentAccount->account_display_name }}" 
                     class="w-5 h-5 mr-3 rounded object-cover">
                @else
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                @endif
                {{ __translator('Account') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif
        {{-- If not enabled at admin level, don't show at all --}}

        {{-- Dashboard --}}
        @php
            $isDashboardEnabled = $menuItemEnabled['dashboard'] ?? true;
            $hasDashboardAccess = $canAccessAccountDashboard ?? true;
        @endphp
        @if($isDashboardEnabled && $hasDashboardAccess)
        <a href="/account/dashboard" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('account/dashboard') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            {{ __translator('Dashboard') }}
        </a>
        @elseif($isDashboardEnabled)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
                {{ __translator('Dashboard') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif

        {{-- Customers --}}
        @if($canAccessCustomers ?? true)
        <a href="/account/customers" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            {{ __translator('Customers') }}
        </a>
        @endif

        {{-- Transactions (optional module) --}}
        @php
            $isTransactionsEnabled = $menuItemEnabled['transactions'] ?? true;
            $hasTransactionAccess = $canViewTransactionHistory ?? false;
        @endphp
        @if($isTransactionsEnabled && $hasTransactionAccess)
        <a href="/modules/transactions" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('modules/transactions*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            {{ __translator('Transactions') }}
        </a>
        @elseif($isTransactionsEnabled)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                {{ __translator('Transactions') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif

        {{-- Billing (optional module) --}}
        @php
            $isBillingEnabled = $menuItemEnabled['billing'] ?? true;
            $hasBillingAccess = $canViewBillingHistory ?? false;
        @endphp
        @if($isBillingEnabled && $hasBillingAccess)
        <a href="/modules/billing" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('modules/billing*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ __translator('Billing') }}
        </a>
        @elseif($isBillingEnabled)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __translator('Billing') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif

        {{-- IBANs (optional module) --}}
        @php
            $isIbansEnabled = $menuItemEnabled['ibans'] ?? true;
            $hasIbanAccess = $canViewIbans ?? false;
        @endphp
        @if($isIbansEnabled && $hasIbanAccess)
        <a href="/modules/ibans" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('modules/ibans*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            {{ __translator('IBANs') }}
        </a>
        @elseif($isIbansEnabled)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                {{ __translator('IBANs') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif

        {{-- Wallets (optional module) --}}
        @php
            $isWalletsEnabled = $menuItemEnabled['wallets'] ?? true;
            $hasWalletAccess = $canViewWallets ?? false;
        @endphp
        @if($isWalletsEnabled && $hasWalletAccess)
        <a href="/modules/wallets" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('modules/wallets*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            {{ __translator('Wallets') }}
        </a>
        @elseif($isWalletsEnabled)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                {{ __translator('Wallets') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif

        {{-- Fees (optional module) --}}
        @if($canViewFees ?? false)
        <a href="/modules/fees" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color);">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __translator('Fees') }}
        </a>
        @endif

        {{-- Developer (optional module) --}}
        @php
            $isDeveloperEnabled = $menuItemEnabled['developer'] ?? true;
            $hasDeveloperAccess = $canAccessDeveloperTools ?? false;
        @endphp
        @if($isDeveloperEnabled && $hasDeveloperAccess)
        <a href="/modules/developer" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('modules/developer*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            {{ __translator('Developer') }}
        </a>
        @elseif($isDeveloperEnabled)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                {{ __translator('Developer') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif

        {{-- Team (disabled for Personal accounts or no permission) --}}
        @php
            $activeAccount = \App\Models\TenantAccount::find(session('active_account_id'));
            $isPersonalAccount = $activeAccount && $activeAccount->account_type === 'personal_individual';
            $isTeamEnabled = $menuItemEnabled['team'] ?? true;
            $hasTeamAccess = $canManageTeamMembers ?? true;
        @endphp
        @if($isTeamEnabled && $isPersonalAccount)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                {{ __translator('Team') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Add / Select a Business Account to invite Team members.') }}
            </div>
        </div>
        @elseif($isTeamEnabled && $hasTeamAccess)
        <a href="/account/team" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('account/team*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            {{ __translator('Team') }}
        </a>
        @elseif($isTeamEnabled)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                {{ __translator('Team') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif

    </div>

    <div class="my-4 border-t" style="border-color: var(--sidebar-hover-background-color);"></div>

    {{-- Member Profile --}}
    <a href="/member/settings" 
       class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
       style="color: var(--sidebar-text-color); {{ request()->is('member/settings*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
        @if(auth()->user()->profile_avatar_image_path)
        <img src="{{ asset('storage/' . auth()->user()->profile_avatar_image_path) }}" 
             alt="{{ auth()->user()->full_name }}" 
             class="w-5 h-5 mr-3 rounded-full object-cover">
        @else
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        @endif
        {{ __translator('My Profile') }}
    </a>

    {{-- Support (links to admin interface for admins, module for users) --}}
    @if(auth()->user()->is_platform_administrator)
    <a href="/administrator/support-tickets" 
       class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
       style="color: var(--sidebar-text-color); {{ request()->is('administrator/support-tickets*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        {{ __translator('Support') }}
    </a>
    @else
        @php
            $isSupportEnabled = $menuItemEnabled['support'] ?? true;
            $hasSupportAccess = $canAccessSupportTickets ?? false;
        @endphp
        @if($isSupportEnabled && $hasSupportAccess)
        <a href="/modules/support" 
           class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors"
           style="color: var(--sidebar-text-color); {{ request()->is('modules/support*') ? 'background-color: var(--sidebar-hover-background-color); border-left: 3px solid var(--brand-primary-color); padding-left: calc(0.75rem - 3px);' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            {{ __translator('Support') }}
        </a>
        @elseif($isSupportEnabled)
        <div class="relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <span class="sidebar-menu-item flex items-center px-3 py-2 rounded-md text-sm transition-colors cursor-not-allowed opacity-50"
                  style="color: var(--sidebar-text-color);">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                {{ __translator('Support') }}
            </span>
            <div x-show="showTooltip" 
                 x-cloak
                 class="fixed px-3 py-2 rounded-md text-sm z-[9999] shadow-lg"
                 style="left: 270px; min-width: 280px; background-color: var(--card-background-color); color: var(--status-warning-color); border: 1px solid var(--sidebar-hover-background-color);">
                {{ __translator('Access denied - contact your account administrator') }}
            </div>
        </div>
        @endif
    @endif

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
            {{ __translator('Exit') }}
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
        {{ __translator('Login / Register') }}
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
