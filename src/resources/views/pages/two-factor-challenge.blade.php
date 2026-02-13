@extends('layouts.guest')

@section('content')
{{-- Two-Factor Authentication Challenge Page --}}

<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
<div class="w-full max-w-md">
    <div class="rounded-lg p-8" style="background-color: var(--card-background-color);">
        {{-- Shield icon --}}
        <div class="flex justify-center mb-4">
            <svg style="width: 48px; height: 48px; color: var(--brand-primary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold mb-2 text-center">{{ __translator('Two-Factor Authentication') }}</h1>
        
        <p class="text-center opacity-70 mb-6">
            {{ __translator('Enter the 6-digit code from your authenticator app.') }}
        </p>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('two-factor.challenge.verify') }}" id="two-factor-form">
            @csrf
            <input type="hidden" name="code" id="two-factor-hidden-code">
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-4 text-center">{{ __translator('Authentication Code') }}</label>
                
                {{-- Individual digit boxes --}}
                <div class="flex justify-center gap-2" id="two-factor-inputs">
                    @for($i = 0; $i < 6; $i++)
                    <input type="text" 
                           class="two-factor-digit text-center text-xl font-bold rounded-md border-2 focus:outline-none focus:ring-2 focus:ring-opacity-50 transition-all"
                           style="width: 42px; height: 52px; background-color: var(--content-background-color); color: var(--content-text-color); border-color: var(--sidebar-hover-background-color);"
                           maxlength="1"
                           inputmode="numeric"
                           pattern="[0-9]"
                           autocomplete="one-time-code"
                           data-index="{{ $i }}"
                           {{ $i === 0 ? 'autofocus' : '' }}>
                    @endfor
                </div>
            </div>

            <button type="submit" 
                    id="two-factor-submit-btn"
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors mb-4"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Verify') }}
            </button>
        </form>

        <div class="mt-6 pt-4 border-t text-center" style="border-color: var(--sidebar-hover-background-color);">
            <a href="{{ route('login-register') }}" 
               class="text-sm opacity-70 hover:opacity-100">
                {{ __translator('← Back to login') }}
            </a>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
(function() {
    const inputs = document.querySelectorAll('.two-factor-digit');
    const form = document.getElementById('two-factor-form');
    const hiddenInput = document.getElementById('two-factor-hidden-code');
    const submitBtn = document.getElementById('two-factor-submit-btn');
    
    // Focus styling
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.style.borderColor = 'var(--brand-primary-color)';
        });
        input.addEventListener('blur', () => {
            input.style.borderColor = 'var(--sidebar-hover-background-color)';
        });
    });
    
    // Handle input
    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = value.slice(0, 1);
            
            if (value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            
            checkComplete();
        });
        
        // Handle backspace
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
        
        // Handle paste
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text');
            const digits = pasteData.replace(/[^0-9]/g, '').slice(0, 6);
            
            digits.split('').forEach((digit, i) => {
                if (inputs[i]) {
                    inputs[i].value = digit;
                }
            });
            
            const lastIndex = Math.min(digits.length, inputs.length) - 1;
            if (lastIndex >= 0) {
                inputs[Math.min(lastIndex + 1, inputs.length - 1)].focus();
            }
            
            checkComplete();
        });
    });
    
    // Check if all digits filled and auto-submit
    function checkComplete() {
        const code = Array.from(inputs).map(i => i.value).join('');
        hiddenInput.value = code;
        
        if (code.length === 6 && /^[0-9]{6}$/.test(code)) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> {{ __translator("Verifying...") }}';
            submitBtn.style.opacity = '0.7';
            
            setTimeout(() => form.submit(), 300);
        }
    }
    
    // Form submit - collect digits
    form.addEventListener('submit', (e) => {
        const code = Array.from(inputs).map(i => i.value).join('');
        hiddenInput.value = code;
        
        if (code.length !== 6) {
            e.preventDefault();
            inputs[0].focus();
        }
    });
})();
</script>
@endpush
@endsection
