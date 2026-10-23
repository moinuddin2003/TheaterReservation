<form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label for="update_password_current_password" class="block text-sm text-cine-secondary">Current password</label>
        <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="mt-2 block w-full rounded-xl border-cine-border/10 bg-cine-bg text-cine-primary">
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
    </div>

    <div>
        <label for="update_password_password" class="block text-sm text-cine-secondary">New password</label>
        <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-xl border-cine-border/10 bg-cine-bg text-cine-primary">
        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
    </div>

    <div>
        <label for="update_password_password_confirmation" class="block text-sm text-cine-secondary">Confirm password</label>
        <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-xl border-cine-border/10 bg-cine-bg text-cine-primary">
        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4">
        <button type="submit" class="rounded-xl bg-cine-accent px-4 py-2 text-sm font-semibold text-white">
            Save password
        </button>
        @if (session('status') === 'password-updated')
            <p class="text-sm text-cine-success">Password updated.</p>
        @endif
    </div>
</form>
