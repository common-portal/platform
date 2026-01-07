@extends('layouts.platform')

@section('content')
{{-- Create Support Ticket --}}

<div class="max-w-2xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="{{ route('modules.support.index') }}" class="text-sm opacity-70 hover:opacity-100 mr-4">← Back</a>
        <h1 class="text-2xl font-bold">New Support Ticket</h1>
    </div>

    @if($errors->any())
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <form method="POST" action="{{ route('modules.support.store') }}">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Subject</label>
                <input type="text" 
                       name="ticket_subject_line" 
                       value="{{ old('ticket_subject_line') }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="Brief description of your issue"
                       required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="ticket_description_body" 
                          rows="8"
                          class="w-full px-4 py-2 rounded-md border-0"
                          style="background-color: var(--content-background-color); color: var(--content-text-color);"
                          placeholder="Please provide as much detail as possible..."
                          required>{{ old('ticket_description_body') }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" 
                        class="px-6 py-2 rounded-md font-medium"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    Submit Ticket
                </button>
                <a href="{{ route('modules.support.index') }}" 
                   class="px-6 py-2 rounded-md font-medium"
                   style="background-color: var(--sidebar-hover-background-color);">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
