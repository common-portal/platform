@extends('layouts.platform')

@section('content')
{{-- Developer Tools Module --}}

<div class="max-w-4xl mx-auto" x-data="{ 
    activeTab: '{{ $activeTab ?? 'documentation' }}',
    editingWebhook: null,
    showAddForm: false
}">
    <h1 class="text-2xl font-bold mb-6">{{ __translator('Developer Tools') }}</h1>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="flex mb-6 rounded-lg overflow-hidden" style="background-color: var(--sidebar-hover-background-color); border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
        <button @click="activeTab = 'documentation'" 
                :class="activeTab === 'documentation' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-right: 1px solid rgba(255,255,255,0.15); border-bottom: 2px solid ' + (activeTab === 'documentation' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'documentation' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __translator('API Documentation') }}
            </span>
        </button>
        <button @click="activeTab = 'keys'" 
                :class="activeTab === 'keys' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-right: 1px solid rgba(255,255,255,0.15); border-bottom: 2px solid ' + (activeTab === 'keys' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'keys' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                {{ __translator('API Keys') }}
            </span>
        </button>
        <button @click="activeTab = 'webhooks'" 
                :class="activeTab === 'webhooks' ? 'opacity-100' : 'opacity-60 hover:opacity-80'"
                :style="'border-bottom: 2px solid ' + (activeTab === 'webhooks' ? 'var(--brand-primary-color)' : 'transparent') + '; background-color: ' + (activeTab === 'webhooks' ? 'var(--card-background-color)' : 'transparent') + ';'"
                class="flex-1 px-4 py-3 text-sm font-medium">
            <span class="flex items-center justify-center gap-2">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                {{ __translator('Webhooks') }}
            </span>
        </button>
    </div>

    {{-- API Documentation Tab --}}
    <div x-show="activeTab === 'documentation'" x-cloak>
        <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
            <h2 class="text-lg font-semibold mb-4">{{ __translator('API Documentation') }}</h2>
            <p class="text-sm opacity-70 mb-4">
                {{ __translator('Access the platform API to integrate with your applications.') }}
            </p>
            
            <div class="p-4 rounded-md mb-4" style="background-color: var(--content-background-color);">
                <p class="text-sm font-medium mb-2">{{ __translator('Base URL') }}</p>
                <code class="text-sm" style="color: var(--brand-primary-color);">
                    {{ config('app.url') }}/api/v1
                </code>
            </div>

            <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                <p class="text-sm font-medium mb-2">{{ __translator('Authentication') }}</p>
                <p class="text-sm opacity-70">
                    {{ __translator('All API requests require a valid API key in the Authorization header:') }}
                </p>
                <code class="text-xs mt-2 block" style="color: var(--brand-primary-color);">
                    Authorization: Bearer YOUR_API_KEY
                </code>
            </div>
        </div>
    </div>

    {{-- API Keys Tab --}}
    <div x-show="activeTab === 'keys'" x-cloak x-data="{ showAddKeyForm: false }">
        <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">{{ __translator('API Keys') }}</h2>
                <button type="button" 
                        @click="showAddKeyForm = !showAddKeyForm"
                        class="px-4 py-2 rounded-md text-sm font-medium"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    + {{ __translator('Generate New Key') }}
                </button>
            </div>
            
            <p class="text-sm opacity-70 mb-4">
                {{ __translator('Manage API keys for') }} {{ $account->account_display_name }}.
            </p>

            {{-- Add API Key Form --}}
            <div x-show="showAddKeyForm" x-cloak class="mb-6 p-4 rounded-md" style="background-color: var(--content-background-color);">
                <form action="{{ route('modules.apikeys.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __translator('API Key Name') }}</label>
                            <input type="text" 
                                   name="api_key_name" 
                                   placeholder="{{ __translator('e.g., Production API') }}"
                                   required
                                   class="w-full px-3 py-2 rounded-md border-0 text-sm"
                                   style="background-color: var(--sidebar-hover-background-color); color: var(--content-text-color);">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __translator('Gateway API Key') }}</label>
                            <input type="text" 
                                   value="{{ md5(rand() . time()) }}"
                                   disabled
                                   class="w-full px-3 py-2 rounded-md border-0 text-sm opacity-60"
                                   style="background-color: var(--sidebar-hover-background-color); color: var(--content-text-color);">
                            <p class="text-xs opacity-50 mt-1">{{ __translator('Auto-generated. A new key will be created on save.') }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" 
                                @click="showAddKeyForm = false"
                                class="px-4 py-2 rounded-md text-sm"
                                style="background-color: var(--sidebar-hover-background-color);">
                            {{ __translator('Cancel') }}
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 rounded-md text-sm font-medium"
                                style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                            {{ __translator('Generate Key') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- API Key List --}}
            @if(isset($apiKeys) && $apiKeys->count() > 0)
            <div class="space-y-3">
                @foreach($apiKeys as $apiKey)
                <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium">{{ $apiKey->api_key_name }}</span>
                                @if($apiKey->is_enabled)
                                <span class="px-2 py-0.5 rounded text-xs" style="background-color: var(--status-success-color); color: white;">{{ __translator('Active') }}</span>
                                @else
                                <span class="px-2 py-0.5 rounded text-xs" style="background-color: var(--status-warning-color); color: #1a1a2e;">{{ __translator('Disabled') }}</span>
                                @endif
                            </div>
                            <code class="text-xs opacity-70 break-all">{{ $apiKey->gateway_api_key }}</code>
                            <p class="text-xs opacity-50 mt-1">{{ __translator('Created') }}: {{ $apiKey->created_at_timestamp->format('M d, Y H:i') }}</p>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            {{-- Toggle Enable/Disable --}}
                            <form action="{{ route('modules.apikeys.toggle', $apiKey->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="p-2 rounded-md hover:opacity-80 transition-opacity"
                                        style="background-color: var(--sidebar-hover-background-color);"
                                        title="{{ $apiKey->is_enabled ? __translator('Disable') : __translator('Enable') }}">
                                    @if($apiKey->is_enabled)
                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    @else
                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    @endif
                                </button>
                            </form>
                            {{-- Delete --}}
                            <form action="{{ route('modules.apikeys.destroy', $apiKey->id) }}" method="POST" class="inline" 
                                  onsubmit="return confirm('{{ __translator('Are you sure you want to delete this API key? This action cannot be undone.') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 rounded-md hover:opacity-80 transition-opacity"
                                        style="background-color: var(--status-error-color);"
                                        title="{{ __translator('Delete') }}">
                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="white" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="p-8 text-center rounded-md" style="background-color: var(--content-background-color);">
                <p class="text-sm opacity-60">{{ __translator('No API keys generated yet.') }}</p>
                <p class="text-xs opacity-40 mt-2">{{ __translator('Generate your first API key to get started.') }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Webhooks Tab --}}
    <div x-show="activeTab === 'webhooks'" x-cloak>
        <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">{{ __translator('Webhooks') }}</h2>
                <button type="button" 
                        @click="showAddForm = !showAddForm"
                        class="px-4 py-2 rounded-md text-sm font-medium"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    + {{ __translator('Add Webhook') }}
                </button>
            </div>
            
            <p class="text-sm opacity-70 mb-4">
                {{ __translator('Configure webhook endpoints to receive real-time event notifications.') }}
            </p>

            {{-- Add Webhook Form --}}
            <div x-show="showAddForm" x-cloak class="mb-6 p-4 rounded-md" style="background-color: var(--content-background-color);">
                <form action="{{ route('modules.webhooks.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __translator('Webhook Name') }}</label>
                            <input type="text" 
                                   name="webhook_name" 
                                   placeholder="{{ __translator('e.g., Payment Success') }}"
                                   required
                                   class="w-full px-3 py-2 rounded-md border-0 text-sm"
                                   style="background-color: var(--sidebar-hover-background-color); color: var(--content-text-color);">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __translator('Webhook URL') }}</label>
                            <input type="url" 
                                   name="webhook_url" 
                                   placeholder="https://your-domain.com/webhook"
                                   required
                                   class="w-full px-3 py-2 rounded-md border-0 text-sm"
                                   style="background-color: var(--sidebar-hover-background-color); color: var(--content-text-color);">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" 
                                @click="showAddForm = false"
                                class="px-4 py-2 rounded-md text-sm"
                                style="background-color: var(--sidebar-hover-background-color);">
                            {{ __translator('Cancel') }}
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 rounded-md text-sm font-medium"
                                style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                            {{ __translator('Save Webhook') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Webhook List --}}
            @if(isset($webhooks) && $webhooks->count() > 0)
            <div class="space-y-3">
                @foreach($webhooks as $webhook)
                <div class="p-4 rounded-md" style="background-color: var(--content-background-color);">
                    <div x-show="editingWebhook !== {{ $webhook->id }}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium">{{ $webhook->webhook_name }}</span>
                                    @if($webhook->is_enabled)
                                    <span class="px-2 py-0.5 rounded text-xs" style="background-color: var(--status-success-color); color: white;">{{ __translator('Active') }}</span>
                                    @else
                                    <span class="px-2 py-0.5 rounded text-xs" style="background-color: var(--status-warning-color); color: #1a1a2e;">{{ __translator('Disabled') }}</span>
                                    @endif
                                </div>
                                <p class="text-sm opacity-60 break-all">{{ $webhook->webhook_url }}</p>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                {{-- Edit Button --}}
                                <button type="button" 
                                        @click="editingWebhook = {{ $webhook->id }}"
                                        class="p-2 rounded-md hover:opacity-80"
                                        style="background-color: var(--sidebar-hover-background-color);"
                                        title="{{ __translator('Edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                {{-- Toggle Button --}}
                                <form action="{{ route('modules.webhooks.toggle', $webhook->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="p-2 rounded-md hover:opacity-80"
                                            style="background-color: var(--sidebar-hover-background-color);"
                                            title="{{ $webhook->is_enabled ? __translator('Disable') : __translator('Enable') }}">
                                        @if($webhook->is_enabled)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                        @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        @endif
                                    </button>
                                </form>
                                {{-- Delete Button --}}
                                <form action="{{ route('modules.webhooks.destroy', $webhook->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __translator('Are you sure you want to delete this webhook?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 rounded-md hover:opacity-80"
                                            style="background-color: var(--status-error-color);"
                                            title="{{ __translator('Delete') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="white" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Edit Form --}}
                    <div x-show="editingWebhook === {{ $webhook->id }}" x-cloak>
                        <form action="{{ route('modules.webhooks.update', $webhook->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">{{ __translator('Webhook Name') }}</label>
                                    <input type="text" 
                                           name="webhook_name" 
                                           value="{{ $webhook->webhook_name }}"
                                           required
                                           class="w-full px-3 py-2 rounded-md border-0 text-sm"
                                           style="background-color: var(--sidebar-hover-background-color); color: var(--content-text-color);">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">{{ __translator('Webhook URL') }}</label>
                                    <input type="url" 
                                           name="webhook_url" 
                                           value="{{ $webhook->webhook_url }}"
                                           required
                                           class="w-full px-3 py-2 rounded-md border-0 text-sm"
                                           style="background-color: var(--sidebar-hover-background-color); color: var(--content-text-color);">
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="button" 
                                        @click="editingWebhook = null"
                                        class="px-4 py-2 rounded-md text-sm"
                                        style="background-color: var(--sidebar-hover-background-color);">
                                    {{ __translator('Cancel') }}
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 rounded-md text-sm font-medium"
                                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                    {{ __translator('Update Webhook') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div x-show="!showAddForm" class="p-8 text-center rounded-md" style="background-color: var(--content-background-color);">
                <p class="text-sm opacity-60">{{ __translator('No webhooks configured.') }}</p>
                <p class="text-xs opacity-40 mt-2">{{ __translator('Add your first webhook to receive event notifications.') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
