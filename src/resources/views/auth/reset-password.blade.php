@extends('layouts.guest')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-md rounded-lg p-8" style="background-color: var(--card-background-color);">
        <h1 class="text-2xl font-bold mb-6 text-center">Reset Password</h1>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Email Address</label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email', $request->email) }}"
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="you@example.com"
                       required 
                       autofocus 
                       autocomplete="username">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">New Password</label>
                <input type="password" 
                       name="password" 
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="••••••••"
                       required 
                       autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Confirm Password</label>
                <input type="password" 
                       name="password_confirmation" 
                       class="w-full px-4 py-2 rounded-md border-0 focus:ring-2"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="••••••••"
                       required 
                       autocomplete="new-password">
            </div>

            <button type="submit" 
                    class="w-full px-4 py-3 rounded-md font-medium transition-colors"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                Reset Password
            </button>
        </form>
    </div>
</div>
@endsection
