{{-- Language Selector Component --}}
{{-- Reference: COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md --}}
{{-- Uses TranslatorService with translate icon prefix + spinner on change --}}

@php
    use App\Services\TranslatorService;
    $currentLang = $preferredLanguage ?? TranslatorService::getCurrentLanguage();
    $languages = TranslatorService::getLanguages();
    $top10 = TranslatorService::getTopLanguages();
@endphp

<div class="language-selector flex items-center">
    {{-- Translate Icon (default) / Spinner (on change) --}}
    <span id="translate-icon" class="mr-2 opacity-60">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" />
        </svg>
    </span>
    <span id="translate-spinner" class="mr-2 hidden">
        <svg class="animate-spin h-5 w-5 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </span>
    
    <select id="language-selector" 
            onchange="changeLanguage(this.value)"
            class="px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
            style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);">
        
        {{-- Top 10 languages first --}}
        @foreach($top10 as $code)
            @if(isset($languages[$code]))
                <option value="{{ $code }}" {{ $code === $currentLang ? 'selected' : '' }}>
                    {{ $languages[$code] }}
                </option>
            @endif
        @endforeach

        {{-- Separator --}}
        <option disabled>──────────────</option>

        {{-- All other languages alphabetically --}}
        @php
            $sorted = $languages;
            asort($sorted);
        @endphp
        @foreach($sorted as $code => $name)
            @if(!in_array($code, $top10))
                <option value="{{ $code }}" {{ $code === $currentLang ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endif
        @endforeach

    </select>
</div>

<script>
    function changeLanguage(languageCode) {
        // Show spinner, hide translate icon
        document.getElementById('translate-icon').classList.add('hidden');
        document.getElementById('translate-spinner').classList.remove('hidden');
        
        fetch('/language', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ language_code: languageCode })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                // Revert icons on failure
                document.getElementById('translate-icon').classList.remove('hidden');
                document.getElementById('translate-spinner').classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Language change failed:', error);
            // Revert icons on error
            document.getElementById('translate-icon').classList.remove('hidden');
            document.getElementById('translate-spinner').classList.add('hidden');
        });
    }
</script>
