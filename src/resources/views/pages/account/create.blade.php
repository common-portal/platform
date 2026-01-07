@extends('layouts.platform')

@section('content')
{{-- Create Business Account Page --}}

<div class="max-w-md mx-auto mt-10">
    <div class="rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-6">Create Business Account</h1>
        
        <form method="POST" action="#">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Business Name</label>
                <input type="text" 
                       name="account_display_name" 
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="Your Company Name"
                       required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Primary Contact Email</label>
                <input type="email" 
                       name="primary_contact_email_address" 
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="contact@company.com">
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Create Account
            </button>
        </form>
    </div>
</div>
@endsection
