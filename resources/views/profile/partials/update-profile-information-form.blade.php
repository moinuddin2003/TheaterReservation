<div class="account-form-section">
    <header>
        <h2>Profile details</h2>
        <p>Update your account name and email address.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="account-form">
        @csrf
        @method('patch')

        <div class="account-field">
            <x-input-label for="name" value="Full name" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="account-input"
                :value="old('name', $user->name)"
                required
                autocomplete="name"
            />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="account-field">
            <x-input-label for="email" value="Email address" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="account-input"
                :value="old('email', $user->email)"
                required
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" />
            <p class="account-notice">
                Changing your email requires verifying the new address before booking.
            </p>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="account-verification">
                    <p>Your email address is not verified.</p>
                    <button form="send-verification" class="account-text-button" type="submit">
                        Resend verification email
                    </button>
                    @if (session('status') === 'verification-link-sent')
                        <p class="account-success">A new verification link has been sent.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="account-form__actions">
            <button class="button button--primary" type="submit">Save profile</button>
            @if (session('status') === 'profile-updated')
                <p class="account-success">Saved.</p>
            @endif
        </div>
    </form>
</div>
