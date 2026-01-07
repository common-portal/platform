{{-- Language Selector Component --}}
{{-- Reference: COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md --}}

<div class="language-selector">
    <label class="block text-xs uppercase tracking-wide opacity-60 mb-2">Language</label>
    <select id="language-selector" 
            onchange="changeLanguage(this.value)"
            class="w-full px-3 py-2 rounded-md text-sm border-0 focus:ring-2 focus:ring-opacity-50"
            style="background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);">
        <option value="eng" {{ ($preferredLanguage ?? 'eng') == 'eng' ? 'selected' : '' }}>English</option>
        <option value="spa" {{ ($preferredLanguage ?? 'eng') == 'spa' ? 'selected' : '' }}>Español</option>
        <option value="fra" {{ ($preferredLanguage ?? 'eng') == 'fra' ? 'selected' : '' }}>Français</option>
        <option value="deu" {{ ($preferredLanguage ?? 'eng') == 'deu' ? 'selected' : '' }}>Deutsch</option>
        <option value="ita" {{ ($preferredLanguage ?? 'eng') == 'ita' ? 'selected' : '' }}>Italiano</option>
        <option value="por" {{ ($preferredLanguage ?? 'eng') == 'por' ? 'selected' : '' }}>Português</option>
        <option value="nld" {{ ($preferredLanguage ?? 'eng') == 'nld' ? 'selected' : '' }}>Nederlands</option>
        <option value="rus" {{ ($preferredLanguage ?? 'eng') == 'rus' ? 'selected' : '' }}>Русский</option>
        <option value="zho" {{ ($preferredLanguage ?? 'eng') == 'zho' ? 'selected' : '' }}>中文</option>
        <option value="jpn" {{ ($preferredLanguage ?? 'eng') == 'jpn' ? 'selected' : '' }}>日本語</option>
        <option value="kor" {{ ($preferredLanguage ?? 'eng') == 'kor' ? 'selected' : '' }}>한국어</option>
        <option value="ara" {{ ($preferredLanguage ?? 'eng') == 'ara' ? 'selected' : '' }}>العربية</option>
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
