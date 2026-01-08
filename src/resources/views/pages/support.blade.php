@extends('layouts.guest')

@section('content')
<div class="max-w-2xl mx-auto px-6 py-12">
    
    {{-- Page Header --}}
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold mb-3">{{ __translator('Contact Support') }}</h1>
        <p class="opacity-70">{{ __translator('Have a question or need assistance? Send us a message.') }}</p>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
    <div class="rounded-lg p-4 mb-6" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
        {{ session('success') }}
    </div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
    <div class="rounded-lg p-4 mb-6 bg-red-600 text-white">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Support Form --}}
    <form action="{{ route('support.submit') }}" method="POST" class="space-y-6">
        @csrf

        {{-- From Name --}}
        <div>
            <label for="from_name" class="block text-sm font-medium mb-2">{{ __translator('From Name') }}</label>
            <input type="text" 
                   id="from_name" 
                   name="from_name" 
                   value="{{ old('from_name') }}"
                   required
                   class="w-full px-4 py-3 rounded-md border-0 focus:ring-2"
                   style="background-color: var(--card-background-color); color: var(--content-text-color);"
                   placeholder="{{ __translator('Your full name') }}">
        </div>

        {{-- From Email Address --}}
        <div>
            <label for="from_email" class="block text-sm font-medium mb-2">{{ __translator('From Email Address') }}</label>
            <input type="email" 
                   id="from_email" 
                   name="from_email" 
                   value="{{ old('from_email') }}"
                   required
                   class="w-full px-4 py-3 rounded-md border-0 focus:ring-2"
                   style="background-color: var(--card-background-color); color: var(--content-text-color);"
                   placeholder="your@email.com">
        </div>

        {{-- Subject --}}
        <div>
            <label for="subject" class="block text-sm font-medium mb-2">{{ __translator('Subject') }}</label>
            <select id="subject" 
                    name="subject" 
                    required
                    class="w-full px-4 py-3 rounded-md border-0 focus:ring-2"
                    style="background-color: var(--card-background-color); color: var(--content-text-color);">
                <option value="">{{ __translator('Select a subject...') }}</option>
                <option value="Billing" {{ old('subject') == 'Billing' ? 'selected' : '' }}>Billing</option>
                <option value="Bug Report" {{ old('subject') == 'Bug Report' ? 'selected' : '' }}>Bug Report</option>
                <option value="General Inquiry" {{ old('subject') == 'General Inquiry' ? 'selected' : '' }}>General Inquiry</option>
                <option value="Partnership" {{ old('subject') == 'Partnership' ? 'selected' : '' }}>Partnership</option>
                <option value="Pricing" {{ old('subject') == 'Pricing' ? 'selected' : '' }}>Pricing</option>
                <option value="Sales" {{ old('subject') == 'Sales' ? 'selected' : '' }}>Sales</option>
                <option value="Technical" {{ old('subject') == 'Technical' ? 'selected' : '' }}>Technical</option>
                <option value="Other" {{ old('subject') == 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>

        {{-- Message --}}
        <div>
            <label for="message" class="block text-sm font-medium mb-2">{{ __translator('Message') }}</label>
            <textarea id="message" 
                      name="message" 
                      rows="6"
                      required
                      class="w-full px-4 py-3 rounded-md border-0 focus:ring-2 resize-none"
                      style="background-color: var(--card-background-color); color: var(--content-text-color);"
                      placeholder="{{ __translator('How can we help you?') }}">{{ old('message') }}</textarea>
        </div>

        {{-- Submit Button --}}
        <div>
            <button type="submit" 
                    class="w-full px-6 py-3 text-lg font-medium rounded-md transition-colors hover:opacity-90"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Submit Form Now') }}
            </button>
        </div>

    </form>

</div>
@endsection
