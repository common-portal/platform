{{-- Action Button Component --}}
{{-- Reference: COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md → Action Button UX Patterns --}}
{{-- 
    Usage: 
    <x-action-button type="submit" text="Save Changes" />
    <x-action-button type="button" text="Send Invitation" onclick="sendInvite()" />
--}}

@props([
    'type' => 'submit',
    'text' => 'Submit',
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
        default => 'px-4 py-2 text-sm',
    };
    
    $variantStyles = match($variant) {
        'primary' => 'background-color: var(--button-background-color); color: var(--button-text-color);',
        'secondary' => 'background-color: var(--sidebar-hover-background-color); color: var(--sidebar-text-color);',
        'danger' => 'background-color: var(--status-error-color); color: white;',
        'success' => 'background-color: var(--status-success-color); color: white;',
        default => 'background-color: var(--button-background-color); color: var(--button-text-color);',
    };
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $baseClasses . ' ' . $sizeClasses]) }}
    style="{{ $variantStyles }}"
    {{ $disabled ? 'disabled' : '' }}
    onclick="handleActionClick(this, event)"
    data-original-text="{{ $text }}"
>
    <span class="button-text">{{ $text }}</span>
    <svg class="button-spinner hidden w-5 h-5 ml-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
</button>

@once
<script>
    function handleActionClick(button, event) {
        if (button.disabled) {
            event.preventDefault();
            return;
        }
        
        const textSpan = button.querySelector('.button-text');
        const spinner = button.querySelector('.button-spinner');
        const originalText = button.dataset.originalText;
        
        button.disabled = true;
        textSpan.textContent = 'Processing...';
        spinner.classList.remove('hidden');
        
        setTimeout(() => {
            button.disabled = false;
            textSpan.textContent = originalText;
            spinner.classList.add('hidden');
        }, 30000);
    }
    
    function resetActionButton(button) {
        const textSpan = button.querySelector('.button-text');
        const spinner = button.querySelector('.button-spinner');
        const originalText = button.dataset.originalText;
        
        button.disabled = false;
        textSpan.textContent = originalText;
        spinner.classList.add('hidden');
    }
</script>
@endonce
