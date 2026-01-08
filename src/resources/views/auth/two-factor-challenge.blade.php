@extends('layouts.guest')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-md rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-2 text-center">{{ __translator('Two-Factor Authentication') }}</h1>

        <div x-data="{ recovery: false }">
            <p class="text-center opacity-70 mb-6" x-show="! recovery">
                {{ __translator('Enter the authentication code from your authenticator app.') }}
            </p>

            <p class="text-center opacity-70 mb-6" x-cloak x-show="recovery">
                {{ __translator('Enter one of your emergency recovery codes.') }}
            </p>

            @if($errors->any())
            <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('two-factor.login') }}">
                @csrf

                <div class="mb-4" x-show="! recovery">
                    <label class="block text-sm font-medium mb-2">{{ __translator('Authentication Code') }}</label>
                    <input type="text" 
                           name="code" 
                           class="w-full px-4 py-3 rounded-md border-0 focus:ring-2 text-center text-2xl tracking-widest"
                           style="background-color: var(--content-background-color); color: var(--content-text-color);"
                           placeholder="000000"
                           inputmode="numeric"
                           autofocus 
                           x-ref="code"
                           autocomplete="one-time-code">
                </div>

                <div class="mb-4" x-cloak x-show="recovery">
                    <label class="block text-sm font-medium mb-2">{{ __translator('Recovery Code') }}</label>
                    <input type="text" 
                           name="recovery_code" 
                           class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                           style="background-color: var(--content-background-color); color: var(--content-text-color);"
                           placeholder="XXXX-XXXX"
                           x-ref="recovery_code"
                           autocomplete="one-time-code">
                </div>

                <button type="submit" 
                        class="w-full px-4 py-3 rounded-md font-medium transition-colors mb-4"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    {{ __translator('Log in') }}
                </button>

                <div class="text-center">
                    <button type="button" 
                            class="text-sm hover:underline cursor-pointer"
                            style="color: var(--hyperlink-text-color);"
                            x-show="! recovery"
                            x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                        {{ __translator('Use a recovery code') }}
                    </button>

                    <button type="button" 
                            class="text-sm hover:underline cursor-pointer"
                            style="color: var(--hyperlink-text-color);"
                            x-cloak
                            x-show="recovery"
                            x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                        {{ __translator('Use an authentication code') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
