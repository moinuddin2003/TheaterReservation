<x-guest-layout
    title="Choose a new password"
    subtitle="Set a new password for your CineBook account."
    compact
>
    <svg class="auth-mail-icon" aria-hidden="true" width="32" height="32" viewBox="0 0 32 32" fill="none">
        <rect x="7" y="14" width="18" height="14" rx="3" stroke="currentColor" stroke-width="2" />
        <path d="M11 14V10a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" />
        <circle cx="16" cy="21" r="1.5" fill="currentColor" />
    </svg>

    <div class="auth-notice auth-notice--card">
        <strong>Almost back to booking</strong>
        <span>Enter the email address that received your reset link, then choose and confirm your new password.</span>
    </div>

    <form class="auth-form" method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="auth-field">
            <x-input-label for="email" value="Email address" />
            <x-text-input
                id="email"
                class="auth-input"
                type="email"
                name="email"
                :value="old('email', $request->email)"
                placeholder="you@example.com"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="auth-field">
            <x-input-label for="password" value="New password" />
            <x-text-input
                id="password"
                class="auth-input"
                type="password"
                name="password"
                placeholder="Enter a new password"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="auth-field">
            <x-input-label for="password_confirmation" value="Confirm new password" />
            <x-text-input
                id="password_confirmation"
                class="auth-input"
                type="password"
                name="password_confirmation"
                placeholder="Enter your new password again"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <button class="button button--primary auth-submit" type="submit">
            Reset password <span aria-hidden="true">→</span>
        </button>
    </form>

    <p class="auth-switch">Link expired or didn’t arrive? <a href="{{ route('password.request') }}">Request another link →</a></p>
</x-guest-layout>
