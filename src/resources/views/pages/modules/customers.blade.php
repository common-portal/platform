@extends('layouts.platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold" style="color: var(--sidebar-text-color);">
            {{ __translator('Customers') }}
        </h1>
        <p class="mt-2 opacity-70" style="color: var(--sidebar-text-color);">
            {{ __translator('Manage your customer database and relationships.') }}
        </p>
    </div>

    @if(session('status'))
    <div class="mb-6 px-4 py-3 rounded-md" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
        {{ session('status') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 px-4 py-3 rounded-md" style="background-color: var(--status-error-color); color: white;">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Customer List --}}
    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        @if($customers->isEmpty())
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--sidebar-text-color);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium" style="color: var(--sidebar-text-color);">
                {{ __translator('No customers yet') }}
            </h3>
            <p class="mt-2 opacity-70" style="color: var(--sidebar-text-color);">
                {{ __translator('Your customer list will appear here once you start adding customers.') }}
            </p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y" style="border-color: var(--sidebar-hover-background-color);">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--sidebar-text-color); opacity: 0.7;">
                            {{ __translator('Name') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--sidebar-text-color); opacity: 0.7;">
                            {{ __translator('Email') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--sidebar-text-color); opacity: 0.7;">
                            {{ __translator('Status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--sidebar-text-color); opacity: 0.7;">
                            {{ __translator('Created') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--sidebar-hover-background-color);">
                    @foreach($customers as $customer)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap" style="color: var(--sidebar-text-color);">
                            {{ $customer->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" style="color: var(--sidebar-text-color);">
                            {{ $customer->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                  style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                                {{ $customer->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--sidebar-text-color); opacity: 0.7;">
                            {{ $customer->created_at->format('Y-m-d') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
