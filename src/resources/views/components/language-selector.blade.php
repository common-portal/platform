{{-- Language Selector Component --}}
{{-- Reference: COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md --}}
{{-- Uses TranslatorService::renderLanguageSelector() for top 10 + separator + all languages --}}

@php
    use App\Services\TranslatorService;
    $currentLang = $preferredLanguage ?? TranslatorService::getCurrentLanguage();
    $languages = TranslatorService::getLanguages();
    $top10 = TranslatorService::getTopLanguages();
@endphp

<div class="language-selector">
    <label class="block text-xs uppercase tracking-wide opacity-60 mb-2">Language</label>
    <select id="language-selector" 
            onchange="changeLanguage(this.value)"
            class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
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
            }
        })
        .catch(error => console.error('Language change failed:', error));
    }
</script>
