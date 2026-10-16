<x-admin.layout :title="$screen->exists ? 'Edit screen' : 'Create screen'" breadcrumb="Cinema">
    <x-admin.page-header :title="$screen->exists ? 'Edit screen' : 'Create a screen'" subtitle="Screen dimensions generate seats using unique row and seat positions.">
        <x-slot:actions><x-admin.button variant="secondary" href="{{ route('admin.cinema.index') }}">Back to cinema</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />
    <x-admin.card>
        <form method="POST" action="{{ $screen->exists ? route('admin.screens.update', $screen) : route('admin.screens.store') }}" class="space-y-5" x-data="{ saving: false }" x-on:submit="if (saving) { $event.preventDefault(); return; } saving = true">
            @csrf @if ($screen->exists) @method('PUT') @endif
            <x-admin.select name="cinema_id" label="Cinema" :value="$screen->cinema_id ?? request('cinema_id')" required>
                <option value="">Choose cinema</option>
                @foreach ($cinemas as $cinema)<option value="{{ $cinema->id }}" @selected(old('cinema_id', $screen->cinema_id ?? request('cinema_id')) == $cinema->id)>{{ $cinema->name }}</option>@endforeach
            </x-admin.select>
            <x-admin.input name="name" label="Screen name" :value="$screen->name" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.input name="rows_count" label="Rows" type="number" min="1" max="26" :value="$screen->rows_count ?: 8" required />
                <x-admin.input name="seats_per_row" label="Seats per row" type="number" min="1" max="99" :value="$screen->seats_per_row ?: 12" required />
            </div>
            <x-admin.toggle name="is_active" label="Screen is active" :checked="$screen->exists ? $screen->is_active : true" />
            @if ($screen->exists && $screen->showtimes()->where('starts_at', '>', now())->exists())
                <p class="rounded-xl border border-cine-warning/20 bg-cine-warning/10 p-3 text-sm text-cine-warning">Future showtimes exist; this screen cannot be resized until they are moved or removed.</p>
            @endif
            <div class="flex justify-end gap-3"><x-admin.button variant="secondary" href="{{ route('admin.cinema.index') }}">Cancel</x-admin.button><x-admin.button type="submit" x-bind:disabled="saving">Save screen and seats</x-admin.button></div>
        </form>
        @if ($screen->exists && ! $screen->showtimes()->where('starts_at', '>', now())->exists())
            <div class="mt-5 border-t border-cine-border/10 pt-5">
                <x-admin.confirm-dialog :action="route('admin.screens.destroy', $screen)" method="DELETE" title="Delete screen" message="Delete this screen and its seats? Screens with bookings or showtimes cannot be deleted.">
                    <x-admin.button variant="danger" x-on:click="open = true">Delete screen</x-admin.button>
                </x-admin.confirm-dialog>
            </div>
        @endif
    </x-admin.card>
</x-admin.layout>
