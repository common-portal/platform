@extends('layouts.platform')

@section('content')
{{-- Admin Theme Settings Page --}}

<div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Platform Theme</h1>
        <a href="{{ route('admin.index') }}" class="text-sm opacity-70 hover:opacity-100">← Back to Admin</a>
    </div>

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

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <form method="POST" action="{{ route('admin.theme.update') }}">
            @csrf
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Platform Name</label>
                <input type="text" 
                       name="platform_display_name" 
                       value="{{ old('platform_display_name', $settings['platform_display_name']) }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Meta Description</label>
                <textarea name="social_sharing_meta_description" 
                          rows="2"
                          class="w-full px-4 py-2 rounded-md border-0"
                          style="background-color: var(--content-background-color); color: var(--content-text-color);"
                          placeholder="Brief description for search engines and social sharing">{{ old('social_sharing_meta_description', $settings['social_sharing_meta_description']) }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Theme Preset</label>
                <select name="active_theme_preset_name" 
                        class="w-full px-4 py-2 rounded-md border-0"
                        style="background-color: var(--content-background-color); color: var(--content-text-color);">
                    <option value="default_dark" {{ $settings['active_theme_preset_name'] === 'default_dark' ? 'selected' : '' }}>Dark Theme</option>
                    <option value="default_light" {{ $settings['active_theme_preset_name'] === 'default_light' ? 'selected' : '' }}>Light Theme</option>
                </select>
            </div>

            <div class="mb-6 p-4 rounded-lg" style="background-color: var(--content-background-color);">
                <p class="text-sm font-medium mb-2">Current Assets</p>
                <p class="text-sm opacity-70">Logo: {{ $settings['platform_logo_image_path'] ?: 'Not set' }}</p>
                <p class="text-sm opacity-70">Favicon: {{ $settings['platform_favicon_image_path'] ?: 'Not set' }}</p>
                <p class="text-sm opacity-70">OG Image: {{ $settings['social_sharing_preview_image_path'] ?: 'Not set' }}</p>
                <p class="text-xs opacity-50 mt-2">File uploads can be added in a future update.</p>
            </div>

            <button type="submit" 
                    class="px-6 py-2 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Save Theme Settings
            </button>
        </form>
    </div>
</div>
@endsection
