@extends('layouts.platform')

@section('content')
{{-- Account Settings Page --}}

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Account Settings</h1>

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

    @if($account)
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Account Details</h2>
            <span class="text-sm px-2 py-1 rounded" style="background-color: var(--sidebar-hover-background-color);">
                {{ $account->account_type === 'personal_individual' ? 'Personal' : 'Business' }}
            </span>
        </div>
        
        <form method="POST" action="{{ route('account.settings.update') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Account Name</label>
                <input type="text" 
                       name="account_display_name" 
                       value="{{ old('account_display_name', $account->account_display_name) }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       {{ $canEdit ? '' : 'disabled' }}>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Primary Contact Name</label>
                <input type="text" 
                       name="primary_contact_full_name" 
                       value="{{ old('primary_contact_full_name', $account->primary_contact_full_name) }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       {{ $canEdit ? '' : 'disabled' }}>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Primary Contact Email</label>
                <input type="email" 
                       name="primary_contact_email_address" 
                       value="{{ old('primary_contact_email_address', $account->primary_contact_email_address) }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       {{ $canEdit ? '' : 'disabled' }}>
            </div>

            @if($account->account_type === 'business_entity')
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Whitelabel Subdomain</label>
                <div class="flex items-center">
                    <input type="text" 
                           name="whitelabel_subdomain_slug" 
                           value="{{ old('whitelabel_subdomain_slug', $account->whitelabel_subdomain_slug) }}"
                           class="flex-1 px-4 py-2 rounded-l-md border-0"
                           style="background-color: var(--content-background-color); color: var(--content-text-color);"
                           placeholder="yourcompany"
                           pattern="[a-z0-9\-]+"
                           {{ $canEdit ? '' : 'disabled' }}>
                    <span class="px-3 py-2 rounded-r-md text-sm opacity-70" style="background-color: var(--sidebar-hover-background-color);">
                        .{{ config('app.base_domain', 'common-portal.nsdb.com') }}
                    </span>
                </div>
                <p class="text-xs opacity-60 mt-1">Optional. Lowercase letters, numbers, and hyphens only.</p>
            </div>
            @endif

            @if($canEdit)
            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Save Changes
            </button>
            @endif
        </form>
    </div>

    {{-- Danger Zone (Business accounts only, owners only) --}}
    @if($account->account_type === 'business_organization' && $membership && $membership->account_membership_role === 'account_owner')
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color); border: 1px solid var(--status-error-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-error-color);">Danger Zone</h2>
        
        <p class="text-sm opacity-70 mb-4">
            Deleting this account will remove it for all team members. This action cannot be undone.
        </p>
        
        <button type="button" 
                onclick="showDeleteModal()"
                class="px-4 py-2 rounded-md font-medium"
                style="background-color: var(--status-error-color); color: white;">
            Delete Account
        </button>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background-color: rgba(0,0,0,0.7);">
        <div class="rounded-lg p-6 max-w-md mx-4" style="background-color: var(--card-background-color);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--status-error-color);">Delete Account</h3>
            
            <p class="mb-4 opacity-70">
                This will permanently delete <strong>{{ $account->account_display_name }}</strong> and remove access for all team members.
            </p>
            
            <form method="POST" action="{{ route('account.delete') }}">
                @csrf
                @method('DELETE')
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Type DELETE to confirm</label>
                    <input type="text" 
                           name="confirm_delete" 
                           class="w-full px-4 py-2 rounded-md border-0"
                           style="background-color: var(--content-background-color); color: var(--content-text-color);"
                           placeholder="DELETE"
                           required>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" 
                            onclick="hideDeleteModal()"
                            class="flex-1 px-4 py-2 rounded-md font-medium"
                            style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 rounded-md font-medium"
                            style="background-color: var(--status-error-color); color: white;">
                        Delete Forever
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showDeleteModal() {
            document.getElementById('delete-modal').classList.remove('hidden');
            document.getElementById('delete-modal').classList.add('flex');
        }
        function hideDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
            document.getElementById('delete-modal').classList.remove('flex');
        }
    </script>
    @endif

    @else
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <p class="opacity-70">No account selected. Please select an account from the sidebar.</p>
    </div>
    @endif
</div>
@endsection
