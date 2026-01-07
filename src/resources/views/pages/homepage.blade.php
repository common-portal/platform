<x-layouts.platform>
    {{-- Homepage --}}
    {{-- Reference: COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md → Homepage Structure --}}

    <div class="max-w-4xl mx-auto">
        
        {{-- Welcome Card --}}
        <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
            <h1 class="text-2xl font-bold mb-2">
                @auth
                    Welcome back, {{ auth()->user()->member_first_name ?: 'there' }}!
                @else
                    Welcome to {{ $platformName ?? 'Common Portal' }}
                @endauth
            </h1>
            <p class="opacity-70">
                @auth
                    Select an account from the sidebar to get started.
                @else
                    Please login or register to continue.
                @endauth
            </p>
        </div>

        @auth
        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            
            {{-- Account Settings --}}
            <a href="/account/settings" 
               class="rounded-lg p-4 transition-all hover:opacity-80"
               style="background-color: var(--card-background-color);">
                <div class="flex items-center mb-2">
                    <svg class="w-6 h-6 mr-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <h3 class="font-semibold">Account Settings</h3>
                </div>
                <p class="text-sm opacity-70">Manage your account details and branding</p>
            </a>

            {{-- Team --}}
            <a href="/account/team" 
               class="rounded-lg p-4 transition-all hover:opacity-80"
               style="background-color: var(--card-background-color);">
                <div class="flex items-center mb-2">
                    <svg class="w-6 h-6 mr-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <h3 class="font-semibold">Team</h3>
                </div>
                <p class="text-sm opacity-70">Invite and manage team members</p>
            </a>

            {{-- My Profile --}}
            <a href="/member/settings" 
               class="rounded-lg p-4 transition-all hover:opacity-80"
               style="background-color: var(--card-background-color);">
                <div class="flex items-center mb-2">
                    <svg class="w-6 h-6 mr-3" style="color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h3 class="font-semibold">My Profile</h3>
                </div>
                <p class="text-sm opacity-70">Update your profile and preferences</p>
            </a>

        </div>

        {{-- Account Overview --}}
        @if($activeAccount ?? null)
        <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4">Current Account</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="opacity-60">Account Name:</span>
                    <p class="font-medium">{{ $activeAccount->account_display_name }}</p>
                </div>
                <div>
                    <span class="opacity-60">Account Type:</span>
                    <p class="font-medium">{{ $activeAccount->account_type === 'personal_individual' ? 'Personal' : 'Business' }}</p>
                </div>
                <div>
                    <span class="opacity-60">Your Role:</span>
                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $memberRole ?? 'Member')) }}</p>
                </div>
                <div>
                    <span class="opacity-60">Team Members:</span>
                    <p class="font-medium">{{ $teamMemberCount ?? 1 }}</p>
                </div>
            </div>
        </div>
        @endif
        @endauth

        @guest
        {{-- Guest CTA --}}
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <h2 class="text-xl font-semibold mb-4">Get Started</h2>
            <p class="opacity-70 mb-6">Create an account or login to access the portal.</p>
            <a href="/login-register" 
               class="inline-flex items-center px-6 py-3 rounded-md font-medium transition-colors"
               style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Login / Register
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
        @endguest

    </div>

</x-layouts.platform>
