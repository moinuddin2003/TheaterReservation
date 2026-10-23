<x-admin.layout title="Profile" breadcrumb="Account">
    @if (session('status') === 'profile-updated')
        <p class="mb-5 rounded-xl border border-cine-success/30 bg-cine-success/10 p-4 text-sm text-cine-success">
            Profile updated.
        </p>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-cine-border/10 bg-cine-surface p-6">
            <h2 class="text-base font-bold text-cine-primary">Account details</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')
                <label class="block text-sm text-cine-secondary">
                    Name
                    <input name="name" value="{{ old('name', $user->name) }}" required class="mt-2 block w-full rounded-xl border-cine-border/10 bg-cine-bg text-cine-primary">
                    @error('name') <span class="text-cine-danger">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm text-cine-secondary">
                    Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-2 block w-full rounded-xl border-cine-border/10 bg-cine-bg text-cine-primary">
                    @error('email') <span class="text-cine-danger">{{ $message }}</span> @enderror
                </label>
                <button class="rounded-xl bg-cine-accent px-4 py-2 text-sm font-semibold text-white" type="submit">Save profile</button>
            </form>
        </section>

        <section class="rounded-2xl border border-cine-border/10 bg-cine-surface p-6">
            <h2 class="text-base font-bold text-cine-primary">Change password</h2>
            @include('profile.partials.update-password-form')
        </section>
    </div>
</x-admin.layout>
