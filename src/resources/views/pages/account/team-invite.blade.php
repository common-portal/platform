@extends('layouts.platform')

@section('content')
{{-- Team Invite Page (Phase 6 placeholder) --}}

<div class="max-w-md mx-auto mt-10">
    <div class="rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-6">{{ __translator('Invite Team Member') }}</h1>

        @if(session('status'))
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-success-color); color: white;">
            {{ session('status') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif
        
        <form method="POST" action="{{ route('account.team.invite.send') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Email Address') }}</label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email') }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="{{ __translator('colleague@example.com') }}"
                       required>
            </div>

            <div class="mb-6">
                <p class="text-sm font-medium mb-2">{{ __translator('Permissions:') }}</p>
                <div class="space-y-2 text-sm">
                    @foreach($allPermissions as $perm)
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="permissions[]" 
                               value="{{ $perm }}"
                               {{ in_array($perm, $defaultPermissions) ? 'checked' : '' }}
                               class="mr-2">
                        {{ $permissionLabels[$perm] ?? $perm }}
                    </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Send Invitation') }}
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('account.team') }}" class="text-sm opacity-70 hover:opacity-100">
                {{ __translator('← Back to Team') }}
            </a>
        </div>
    </div>
</div>
@endsection
