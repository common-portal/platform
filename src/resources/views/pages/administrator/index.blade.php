@extends('layouts.platform')

@section('content')
{{-- Administrator Panel --}}

<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Administrator Panel</h1>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">0</p>
            <p class="text-sm opacity-70">Total Members</p>
        </div>
        
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">0</p>
            <p class="text-sm opacity-70">Total Accounts</p>
        </div>
        
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">0</p>
            <p class="text-sm opacity-70">Business Accounts</p>
        </div>
        
        <div class="rounded-lg p-6 text-center" style="background-color: var(--card-background-color);">
            <p class="text-3xl font-bold" style="color: var(--brand-primary-color);">0</p>
            <p class="text-sm opacity-70">Pending Invitations</p>
        </div>
    </div>

    {{-- Admin Tabs Placeholder --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <div class="flex space-x-4 border-b mb-4 pb-2" style="border-color: var(--sidebar-hover-background-color);">
            <button class="px-4 py-2 font-medium border-b-2" style="border-color: var(--brand-primary-color);">Stats</button>
            <button class="px-4 py-2 opacity-70 hover:opacity-100">Members</button>
            <button class="px-4 py-2 opacity-70 hover:opacity-100">Accounts</button>
            <button class="px-4 py-2 opacity-70 hover:opacity-100">Global</button>
            <button class="px-4 py-2 opacity-70 hover:opacity-100">Theme</button>
            <button class="px-4 py-2 opacity-70 hover:opacity-100">Menu Items</button>
        </div>
        
        <p class="opacity-70">Administrator panel will be implemented in a later phase.</p>
    </div>
</div>
@endsection
