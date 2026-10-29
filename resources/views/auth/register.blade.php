<x-guest-layout title="Your seat in the story" subtitle="Create your customer account. Verify your email to hold seats and check out." compact>
    <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf
        <div class="auth-field">
            <x-input-label for="name" value="Full name" />
            <x-text-input id="name" class="auth-input" type="text" name="name" :value="old('name')" placeholder="Your name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="auth-field">
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" class="auth-input" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="auth-field">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="auth-input" type="password" name="password" placeholder="Create a password" required autocomplete="new-password" />
            <p class="auth-help">Use at least 8 characters, including a letter and a number.</p>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="auth-field">
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" placeholder="Enter your password again" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <button class="button button--primary auth-submit" type="submit">Create account <span aria-hidden="true">→</span></button>
    </form>

    <p class="auth-terms">By creating an account, you agree to the reservation and cancellation rules.</p>
    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in →</a></p>
</x-guest-layout>
