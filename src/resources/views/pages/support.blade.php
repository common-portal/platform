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
    <form action="{{ route('support.submit') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
        @csrf

        {{-- From Name --}}
        <div>
            <label for="from_name" class="block text-sm font-medium mb-2">{{ __translator('Your Name') }}</label>
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
                @foreach(\App\Models\SupportTicket::subjectCategories() as $value => $label)
                <option value="{{ $value }}" {{ old('subject') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
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

        {{-- Attachments --}}
        <div>
            <label for="attachments" class="block text-sm font-medium mb-2">{{ __translator('Attachments') }} <span class="opacity-50">({{ __translator('optional') }})</span></label>
            <input type="file" 
                   id="attachments"
                   name="attachments[]" 
                   multiple
                   class="w-full px-4 py-3 rounded-md border-0 text-sm"
                   style="background-color: var(--card-background-color); color: var(--content-text-color);"
                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
            <p class="text-xs opacity-50 mt-1">{{ __translator('Max 5 files. Allowed: images, PDF, DOC, TXT, ZIP (max 10MB each)') }}</p>
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
