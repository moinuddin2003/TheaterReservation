<div class="account-form-section">
    <header>
        <h2>Update password</h2>
        <p>Choose a strong password to help protect your account.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="account-form">
        @csrf
        @method('put')

        <div class="account-field">
            <x-input-label for="update_password_current_password" value="Current password" />
            <x-text-input
                id="update_password_current_password"
                name="current_password"
                type="password"
                class="account-input"
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="account-form__row">
            <div class="account-field">
                <x-input-label for="update_password_password" value="New password" />
                <x-text-input
                    id="update_password_password"
                    name="password"
                    type="password"
                    class="account-input"
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div class="account-field">
                <x-input-label for="update_password_password_confirmation" value="Confirm new password" />
                <x-text-input
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="account-input"
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>

        <p class="account-help">Use at least 8 characters, including a letter and a number.</p>
        <div class="account-form__actions">
            <button class="button button--quiet" type="submit">Update password</button>
            @if (session('status') === 'password-updated')
                <p class="account-success">Saved.</p>
            @endif
        </div>
    </form>
</div>
