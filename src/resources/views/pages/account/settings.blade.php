@extends('layouts.platform')

@section('content')
{{-- Account Settings Page --}}

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">{{ __translator('Account Settings') }}</h1>

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
            <h2 class="text-lg font-semibold">{{ __translator('Account Details') }}</h2>
            <span class="text-sm px-2 py-1 rounded" style="background-color: var(--sidebar-hover-background-color);">
                {{ $account->account_type === 'personal_individual' ? __translator('Personal') : __translator('Business') }}
            </span>
        </div>
        
        {{-- Logo Upload --}}
        @if($canEdit)
        <div class="flex items-center space-x-6 mb-6" x-data="{ preview: null }">
            <div class="relative" style="width: 80px; height: 80px; flex-shrink: 0;">
                {{-- Logo Preview --}}
                <div class="rounded-lg overflow-hidden flex items-center justify-center"
                     style="width: 80px; height: 80px; background-color: var(--content-background-color);">
                    @if($account->branding_logo_image_path)
                        <img src="{{ asset('storage/' . $account->branding_logo_image_path) }}" 
                             alt="{{ __translator('Account Logo') }}"
                             style="max-width: 80px; max-height: 80px; object-fit: contain;"
                             x-show="!preview">
                    @else
                        <svg x-show="!preview" class="w-10 h-10 opacity-40" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5-7l-3 3.72L9 13l-3 4h12l-4-5z"/>
                        </svg>
                    @endif
                    <img x-show="preview" :src="preview" style="max-width: 80px; max-height: 80px; object-fit: contain;">
                </div>
                
                {{-- Upload Button Overlay --}}
                <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-lg opacity-0 hover:opacity-100 transition-opacity cursor-pointer">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <input type="file" 
                           class="hidden" 
                           accept="image/*"
                           form="logo-form"
                           name="logo"
                           @change="
                               const file = $event.target.files[0];
                               if (file) {
                                   preview = URL.createObjectURL(file);
                                   document.getElementById('logo-submit').click();
                               }
                           ">
                </label>
            </div>
            
            <div>
                <p class="text-sm font-medium mb-1">{{ __translator('Account Logo') }}</p>
                <p class="text-xs opacity-60 mb-2">{{ __translator('Click to upload (max 10MB)') }}</p>
                @if($account->branding_logo_image_path)
                <button type="button" 
                        onclick="if(confirm('{{ __translator('Remove account logo?') }}')) document.getElementById('remove-logo-form').submit();"
                        class="text-xs px-3 py-1 rounded"
                        style="background-color: var(--status-error-color); color: white;">
                    {{ __translator('Remove') }}
                </button>
                @endif
            </div>
        </div>
        
        {{-- Hidden Logo Forms --}}
        <form id="logo-form" method="POST" action="{{ route('account.settings.logo') }}" enctype="multipart/form-data" class="hidden">
            @csrf
            <button type="submit" id="logo-submit"></button>
        </form>
        <form id="remove-logo-form" method="POST" action="{{ route('account.settings.logo.remove') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        @endif

        <form method="POST" action="{{ route('account.settings.update') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Account Name') }}</label>
                <input type="text" 
                       name="account_display_name" 
                       value="{{ old('account_display_name', $account->account_display_name) }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       {{ $canEdit ? '' : 'disabled' }}>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Primary Contact Name') }}</label>
                <input type="text" 
                       name="primary_contact_full_name" 
                       value="{{ old('primary_contact_full_name', $account->primary_contact_full_name) }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       {{ $canEdit ? '' : 'disabled' }}>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Primary Contact Email') }}</label>
                <input type="email" 
                       name="primary_contact_email_address" 
                       value="{{ old('primary_contact_email_address', $account->primary_contact_email_address) }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       {{ $canEdit ? '' : 'disabled' }}>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Customer Support Email') }}</label>
                <input type="email" 
                       name="customer_support_email" 
                       value="{{ old('customer_support_email', $account->customer_support_email) }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       {{ $canEdit ? '' : 'disabled' }}>
                <p class="text-xs mt-1 opacity-50">{{ __translator('Used as the Reply-To address on emails sent to your customers.') }}</p>
            </div>

            {{-- Whitelabel Subdomain field hidden per user request
            @if($account->isBusinessAccount())
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Whitelabel Subdomain') }}</label>
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
                <p class="text-xs opacity-60 mt-1">{{ __translator('Optional. Lowercase letters, numbers, and hyphens only.') }}</p>
            </div>
            @endif
            --}}

            @if($canEdit)
            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Save Changes') }}
            </button>
            @endif
        </form>
    </div>

    {{-- Danger Zone (Business accounts only, owners only) --}}
    @if($account->account_type === 'business_organization' && $membership && $membership->account_membership_role === 'account_owner')
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color); border: 1px solid var(--status-error-color);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--status-error-color);">{{ __translator('Danger Zone') }}</h2>
        
        <p class="text-sm opacity-70 mb-4">
            {{ __translator('Deleting this account will remove it for all team members. This action cannot be undone.') }}
        </p>
        
        <button type="button" 
                onclick="showDeleteModal()"
                class="px-4 py-2 rounded-md font-medium"
                style="background-color: var(--status-error-color); color: white;">
            {{ __translator('Delete Account') }}
        </button>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background-color: rgba(0,0,0,0.7);">
        <div class="rounded-lg p-6 max-w-md mx-4" style="background-color: var(--card-background-color);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--status-error-color);">{{ __translator('Delete Account') }}</h3>
            
            <p class="mb-4 opacity-70">
                This will permanently delete <strong>{{ $account->account_display_name }}</strong> and remove access for all team members.
            </p>
            
            <form method="POST" action="{{ route('account.delete') }}">
                @csrf
                @method('DELETE')
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">{{ __translator('Type DELETE to confirm') }}</label>
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
                        {{ __translator('Cancel') }}
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 rounded-md font-medium"
                            style="background-color: var(--status-error-color); color: white;">
                        {{ __translator('Delete Forever') }}
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
        <p class="opacity-70">{{ __translator('No account selected. Please select an account from the sidebar.') }}</p>
    </div>
    @endif
</div>
@endsection
