<x-guest-layout>
    <x-auth-session-status :status="session('status')" />

    <form class="auth-form" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="auth-field">
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" class="auth-input" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="auth-field">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="auth-input" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="auth-form__links">
            <label class="auth-checkbox" for="remember_me">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Remember me</span>
            </label>
            <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>

        <button class="button button--primary auth-submit" type="submit">Sign in <span aria-hidden="true">→</span></button>
    </form>

</x-guest-layout>
