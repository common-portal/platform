@extends('layouts.platform')

@section('content')
{{-- Account Settings Page --}}

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Account Settings</h1>

    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Account Details</h2>
        
        <form method="POST" action="#">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Account Name</label>
                <input type="text" 
                       name="account_display_name" 
                       value="{{ $activeAccount->account_display_name ?? '' }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Primary Contact Name</label>
                <input type="text" 
                       name="primary_contact_full_name" 
                       value="{{ $activeAccount->primary_contact_full_name ?? '' }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Primary Contact Email</label>
                <input type="email" 
                       name="primary_contact_email_address" 
                       value="{{ $activeAccount->primary_contact_email_address ?? '' }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);">
            </div>

            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Save Changes
            </button>
        </form>
    </div>
</div>
@endsection
