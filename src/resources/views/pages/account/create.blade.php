@extends('layouts.platform')

@section('content')
{{-- Create Business Account Page --}}

<div class="max-w-md mx-auto mt-10">
    <div class="rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-6">{{ __translator('Create Business Account') }}</h1>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif
        
        <form method="POST" action="{{ route('account.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Account Name') }}</label>
                <input type="text" 
                       name="account_display_name" 
                       value="{{ old('account_display_name') }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="{{ __translator('My Company') }}"
                       autofocus
                       required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">{{ __translator('Contact Email (optional)') }}</label>
                <input type="email" 
                       name="primary_contact_email_address" 
                       value="{{ old('primary_contact_email_address') }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="{{ auth()->user()->login_email_address }}">
                <p class="text-xs opacity-60 mt-1">{{ __translator('Defaults to your login email if left blank.') }}</p>
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Create Account') }}
            </button>
        </form>
    </div>
</div>
@endsection
