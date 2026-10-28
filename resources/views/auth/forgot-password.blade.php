<x-guest-layout
    title="Forgot your password?"
    subtitle="We’ll send you a secure link to choose a new password."
>
    <div class="auth-mail-icon" aria-hidden="true">✉</div>

    @if (session('status'))
        <div class="auth-notice auth-notice--card" role="status">
            <strong>Check your inbox</strong>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="auth-field">
            <x-input-label for="email" value="Email address" />
            <x-text-input
                id="email"
                class="auth-input"
                type="email"
                name="email"
                :value="old('email')"
                placeholder="you@example.com"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <button class="button button--primary auth-submit" type="submit">
            Send reset link <span aria-hidden="true">→</span>
        </button>
    </form>

    <p class="auth-switch">Remembered your password? <a href="{{ route('login') }}">Sign in →</a></p>
</x-guest-layout>
