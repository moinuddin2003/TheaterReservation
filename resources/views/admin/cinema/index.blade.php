<x-admin.layout title="Cinema & Screens" breadcrumb="Cinema">
    <div x-data="{ createCinemaOpen: false }">
        <x-admin.page-header title="Cinema & Screens" subtitle="Manage cinema profiles, screen capacity, and seat availability.">
            <x-slot:actions><x-admin.button x-on:click="createCinemaOpen = true">Add cinema</x-admin.button><x-admin.button href="{{ route('admin.screens.create') }}">Add screen</x-admin.button></x-slot:actions>
        </x-admin.page-header>
        <x-admin.modal name="createCinemaOpen" title="Create cinema profile">
            <form method="POST" action="{{ route('admin.cinema.store') }}" class="space-y-4" x-data="{ saving: false }" x-on:submit="if (saving) { $event.preventDefault(); return; } saving = true">
                @csrf
                <x-admin.input name="name" label="Cinema name" required />
                <x-admin.input name="address" label="Address" />
                <x-admin.input name="phone" label="Phone" />
                <div class="flex justify-end"><x-admin.button type="submit" x-bind:disabled="saving">Create cinema</x-admin.button></div>
            </form>
        </x-admin.modal>
    <x-admin.toast />

    <div class="mb-6 grid gap-4 xl:grid-cols-2">
        @forelse ($cinemas as $cinema)
            <x-admin.card>
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold text-cine-primary">{{ $cinema->name }}</h2><p class="mt-1 text-sm text-cine-secondary">{{ $cinema->screens_count }} screens · {{ $cinema->address ?: 'No address set' }}</p></div>
                    <x-admin.icon name="armchair" class="h-5 w-5 text-cine-accent" />
                </div>
                <form method="POST" action="{{ route('admin.cinema.update', $cinema) }}" class="space-y-3" x-data="{ saving: false }" x-on:submit="if (saving) { $event.preventDefault(); return; } saving = true">
                    @csrf @method('PATCH')
                    <x-admin.input name="name" label="Cinema name" :value="$cinema->name" required />
                    <x-admin.input name="address" label="Address" :value="$cinema->address" />
                    <x-admin.input name="phone" label="Phone" :value="$cinema->phone" />
                    <x-admin.button type="submit" x-bind:disabled="saving">Save profile</x-admin.button>
                </form>
                @if ($cinema->screens_count === 0)
                    <div class="mt-4 border-t border-cine-border/10 pt-4">
                        <x-admin.confirm-dialog :action="route('admin.cinema.destroy', $cinema)" :id="'cinema-delete-'.$cinema->id" method="DELETE" title="Delete cinema" message="Permanently remove this cinema profile?">
                            <x-admin.button variant="danger" x-on:click="open = true">Delete cinema</x-admin.button>
                        </x-admin.confirm-dialog>
                    </div>
                @endif
            </x-admin.card>
        @empty
            <x-admin.card><x-admin.empty-state title="No cinema profiles" text="Add a cinema profile before creating screens." icon="armchair" /></x-admin.card>
        @endforelse
    </div>

    <div class="mb-3 flex items-end justify-between gap-4">
        <div><h2 class="text-lg font-semibold text-cine-primary">Screens</h2><p class="mt-1 text-sm text-cine-secondary">{{ $screenCount }} screens across all cinema profiles</p></div>
    </div>
    <x-admin.filters-bar :action="route('admin.cinema.index')">
        <label class="text-xs font-semibold text-cine-secondary">Cinema
            <select name="cinema_id" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All cinemas</option>@foreach ($cinemas as $cinema)<option value="{{ $cinema->id }}" @selected(request('cinema_id') == $cinema->id)>{{ $cinema->name }}</option>@endforeach</select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">State
            <select name="active" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All states</option><option value="active" @selected(request('active') === 'active')>Active</option><option value="inactive" @selected(request('active') === 'inactive')>Inactive</option></select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Sort
            <select name="sort" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">@foreach (['name' => 'Screen name', 'seats_count' => 'Seat count', 'upcoming_showtimes_count' => 'Upcoming showtimes'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'name') === $key)>{{ $label }}</option>@endforeach</select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Direction
            <select name="direction" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="asc" @selected(request('direction', 'asc') === 'asc')>Ascending</option><option value="desc" @selected(request('direction') === 'desc')>Descending</option></select>
        </label>
    </x-admin.filters-bar>
    <div class="overflow-x-auto rounded-2xl border border-cine-border/10 bg-cine-surface shadow-cine-card">
        <table class="min-w-full text-left text-sm">
            <thead class="sticky top-0 bg-cine-elevated/80 text-xs uppercase text-cine-tertiary"><tr><th class="px-4 py-3">Screen</th><th class="px-4 py-3">Cinema</th><th class="px-4 py-3">Seats</th><th class="px-4 py-3">Upcoming showtimes</th><th class="px-4 py-3">State</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-cine-border/10">
                @forelse ($screens as $screen)
                    <tr class="hover:bg-cine-elevated/30">
                        <td class="px-4 py-3 font-semibold text-cine-primary">{{ $screen->name }}</td><td class="px-4 py-3 text-cine-secondary">{{ $screen->cinema->name }}</td><td class="px-4 py-3 text-cine-secondary">{{ $screen->seats_count }}</td><td class="px-4 py-3 text-cine-secondary">{{ $screen->upcoming_showtimes_count }}</td><td class="px-4 py-3"><x-admin.status-badge :status="$screen->is_active ? 'active' : 'disabled'" /></td>
                        <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('admin.seats.index', $screen) }}" class="font-semibold text-cine-accent">Seat map</a><a href="{{ route('admin.screens.edit', $screen) }}" class="ml-3 text-cine-secondary hover:text-cine-primary">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12"><x-admin.empty-state title="No screens found" text="Clear your filters or create a screen." icon="armchair" /><div class="text-center"><x-admin.button href="{{ route('admin.screens.create') }}">Add screen</x-admin.button></div></td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-cine-border/10 px-4 py-3">{{ $screens->withQueryString()->links() }}</div>
    </div>
    </div>
</x-admin.layout>
