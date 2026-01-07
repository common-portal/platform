@extends('layouts.platform')

@section('content')
{{-- Admin Menu Items Page --}}

<div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Menu Item Visibility</h1>
        <a href="{{ route('admin.index') }}" class="text-sm opacity-70 hover:opacity-100">← Back to Admin</a>
    </div>

    @if(session('status'))
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
        {{ session('status') }}
    </div>
    @endif

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <p class="text-sm opacity-70 mb-6">
            Toggle which menu items are visible to users. Disabled items will be hidden from the sidebar for all users.
        </p>

        <form method="POST" action="{{ route('admin.menu-items.update') }}">
            @csrf
            
            <div class="space-y-3">
                @foreach($menuItems as $key => $label)
                <label class="flex items-center justify-between p-3 rounded-lg" style="background-color: var(--content-background-color);">
                    <span class="font-medium">{{ $label }}</span>
                    <input type="checkbox" 
                           name="toggles[{{ $key }}]" 
                           value="1"
                           {{ (isset($toggles[$key]) && $toggles[$key]) || !isset($toggles[$key]) ? 'checked' : '' }}
                           class="w-5 h-5">
                </label>
                @endforeach
            </div>

            <div class="mt-6">
                <button type="submit" 
                        class="px-6 py-2 rounded-md font-medium"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    Save Menu Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
