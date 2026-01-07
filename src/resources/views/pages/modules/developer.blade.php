@extends('layouts.platform')

@section('content')
{{-- Developer Tools Module --}}

<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Developer Tools</h1>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    {{-- API Documentation --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">API Documentation</h2>
        <p class="text-sm opacity-70 mb-4">
            Access the platform API to integrate with your applications.
        </p>
        
        <div class="p-4 rounded-md mb-4" style="background-color: var(--content-background-color);">
            <p class="text-sm font-medium mb-2">Base URL</p>
            <code class="text-sm" style="color: var(--brand-primary-color);">
                {{ config('app.url') }}/api/v1
            </code>
        </div>

        <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
            <p class="text-sm font-medium mb-2">Authentication</p>
            <p class="text-sm opacity-70">
                All API requests require a valid API key in the <code>Authorization</code> header:
            </p>
            <code class="text-xs mt-2 block" style="color: var(--brand-primary-color);">
                Authorization: Bearer YOUR_API_KEY
            </code>
        </div>
    </div>

    {{-- API Keys --}}
    <div class="rounded-lg p-6 mb-6" style="background-color: var(--card-background-color);">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">API Keys</h2>
            <button type="button" 
                    class="px-4 py-2 rounded-md text-sm font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);"
                    onclick="alert('API key generation coming soon!')">
                + Generate New Key
            </button>
        </div>
        
        <p class="text-sm opacity-70 mb-4">
            Manage API keys for {{ $account->account_display_name }}.
        </p>

        <div class="p-8 text-center rounded-md" style="background-color: var(--content-background-color);">
            <p class="text-sm opacity-60">No API keys generated yet.</p>
            <p class="text-xs opacity-40 mt-2">Generate your first API key to get started.</p>
        </div>
    </div>

    {{-- Webhooks --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <h2 class="text-lg font-semibold mb-4">Webhooks</h2>
        <p class="text-sm opacity-70 mb-4">
            Configure webhook endpoints to receive real-time event notifications.
        </p>

        <div class="p-8 text-center rounded-md" style="background-color: var(--content-background-color);">
            <p class="text-sm opacity-60">No webhooks configured.</p>
            <p class="text-xs opacity-40 mt-2">Webhook configuration coming soon.</p>
        </div>
    </div>
</div>
@endsection
