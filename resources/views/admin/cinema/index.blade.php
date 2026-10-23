@php
    $fieldClass = 'min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20';
    $labelClass = 'mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary';
@endphp

<x-admin.layout title="Cinema & Screens" breadcrumb="CineBook / Admin / Cinema">
    <x-admin.page-header
        title="Cinema & Screens"
        subtitle="Manage your cinema locations, auditorium capacity, and seat layouts."
    >
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.screens.create') }}">
                <span aria-hidden="true">+</span>
                Add screen
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ session('error') }}</div>
    @endif

    @php
        $statCards = [
            ['label' => 'Cinema locations', 'value' => $stats['cinemas'], 'icon' => 'building'],
            ['label' => 'Active screens', 'value' => $screens->where('is_active', true)->count(), 'icon' => 'monitor'],
            ['label' => 'Total seats', 'value' => $stats['seats'], 'icon' => 'armchair'],
            ['label' => 'Premium seats', 'value' => $stats['premium_seats'], 'icon' => 'star'],
        ];
    @endphp

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $stat)
            <div class="rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg p-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-cine-accent/30 bg-cine-accent/15 text-cine-accent">
                        <x-admin.icon :name="$stat['icon']" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-cine-secondary">{{ $stat['label'] }}</p>
                        <p class="mt-0.5 text-2xl font-bold tracking-tight text-cine-primary">{{ number_format($stat['value']) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(280px,350px)]">
        <div class="space-y-5">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-cine-primary">Your cinemas</h3>
                    <p class="mt-1 text-xs text-cine-secondary">Locations and their connected auditoriums.</p>
                </div>
                <a href="{{ route('admin.screens.index') }}" class="text-xs font-semibold text-cine-accent hover:text-cine-primary">View all screens →</a>
            </div>

            @if ($cinemas->isEmpty())
                <x-admin.card>
                    <x-admin.empty-state icon="building" title="No cinemas yet" text="Create a cinema location, then add screens and generate the seat layouts." />
                </x-admin.card>
            @else
            @foreach ($cinemas as $cinema)
                <x-admin.card class="!p-0">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-cine-border/10 px-5 py-4 sm:px-6">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
                                <x-admin.icon name="building" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <h4 class="truncate font-bold text-cine-primary">{{ $cinema->name }}</h4>
                                <p class="mt-1 text-xs leading-5 text-cine-secondary">{{ $cinema->address ?: 'No address added' }}</p>
                                @if ($cinema->phone)
                                    <p class="mt-0.5 text-xs text-cine-tertiary">{{ $cinema->phone }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-3 text-xs">
                            <x-admin.badge>{{ $cinema->screens_count }} {{ Str::plural('screen', $cinema->screens_count) }}</x-admin.badge>
                            <a href="{{ route('admin.cinema.index', ['edit' => $cinema->id]) }}" class="font-semibold text-cine-accent hover:text-cine-primary">Edit</a>
                            <a href="{{ route('admin.cinema.index', ['delete' => $cinema->id]) }}#cinema-delete" class="font-semibold text-cine-danger hover:text-cine-primary">Delete</a>
                        </div>
                    </div>

                    <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
                        @if ($cinema->screens->isEmpty())
                            <div class="rounded-xl border border-dashed border-cine-border/15 px-4 py-6 text-center sm:col-span-2">
                                <p class="text-sm font-semibold text-cine-primary">No screens at this cinema yet</p>
                                <a href="{{ route('admin.screens.create', ['cinema_id' => $cinema->id]) }}" class="mt-2 inline-flex text-xs font-semibold text-cine-accent hover:text-cine-primary">Add the first screen</a>
                            </div>
                        @else
                        @foreach ($cinema->screens as $screen)
                            <a href="{{ route('admin.screens.show', $screen) }}" class="group rounded-xl border border-cine-border/10 bg-cine-bg/50 p-4 transition hover:border-cine-accent/30 hover:bg-cine-elevated/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cine-elevated text-cine-accent">
                                            <x-admin.icon name="monitor" class="h-4 w-4" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-cine-primary group-hover:text-cine-accent">{{ $screen->name }}</p>
                                            <p class="mt-0.5 text-[11px] text-cine-tertiary">{{ $screen->rows_count }} rows × {{ $screen->seats_per_row }} seats</p>
                                        </div>
                                    </div>
                                    <x-admin.badge :status="$screen->is_active ? 'success' : 'warning'">{{ $screen->is_active ? 'Active' : 'Inactive' }}</x-admin.badge>
                                </div>
                                <div class="mt-4 flex items-center justify-between border-t border-cine-border/10 pt-3 text-[11px] text-cine-secondary">
                                    <span>{{ number_format($screen->seats_count) }} seats · {{ number_format($screen->premium_seats_count) }} premium</span>
                                    <span>{{ number_format($screen->showtimes_count) }} {{ Str::plural('showtime', $screen->showtimes_count) }}</span>
                                </div>
                                <div class="mt-3 flex items-center gap-1" aria-hidden="true">
                                    @for ($seatPreview = 0; $seatPreview < min(12, $screen->seats_count); $seatPreview++)
                                        <span class="h-1.5 flex-1 rounded-full {{ $seatPreview < $screen->disabled_seats_count ? 'bg-cine-disabled' : 'bg-cine-accent/60' }}"></span>
                                    @endfor
                                    @if ($screen->seats_count === 0)
                                        <span class="text-[10px] text-cine-tertiary">Seat layout not generated</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                        @endif
                    </div>
                </x-admin.card>
            @endforeach
            @endif

            @if ($deletingCinema)
                <x-admin.card id="cinema-delete" class="border-cine-danger/20">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-cine-danger">Delete confirmation</p>
                    <h3 class="mt-2 text-sm font-bold text-cine-primary">Delete {{ $deletingCinema->name }}?</h3>
                    @if ($deletingCinema->screens_count > 0)
                        <p class="mt-2 text-xs leading-5 text-cine-secondary">This cinema still has {{ $deletingCinema->screens_count }} {{ Str::plural('screen', $deletingCinema->screens_count) }}. Remove its screens before deleting the location.</p>
                        <x-admin.button :href="route('admin.cinema.index')" variant="secondary" class="mt-4 !min-h-8 !rounded-lg !px-3 !py-1 !text-xs">Keep cinema</x-admin.button>
                    @else
                        <p class="mt-2 text-xs leading-5 text-cine-secondary">This permanently removes the cinema location.</p>
                        <div class="mt-4 flex gap-2">
                            <x-admin.button :href="route('admin.cinema.index')" variant="secondary" class="!min-h-8 !rounded-lg !px-3 !py-1 !text-xs">Keep cinema</x-admin.button>
                            <form method="POST" action="{{ route('admin.cinema.destroy', $deletingCinema) }}">
                                @csrf
                                @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="!min-h-8 !rounded-lg !px-3 !py-1 !text-xs">Confirm deletion</x-admin.button>
                            </form>
                        </div>
                    @endif
                </x-admin.card>
            @endif
        </div>

        <x-admin.card class="xl:mt-[68px]" :title="$editingCinema ? 'Edit cinema' : 'Add a cinema'" :subtitle="$editingCinema ? 'Update location information.' : 'Set up a location before adding screens.'">
            <form method="POST" action="{{ $editingCinema ? route('admin.cinema.update', $editingCinema) : route('admin.cinema.store') }}" class="space-y-4" novalidate>
                @csrf
                @if ($editingCinema)
                    @method('PUT')
                @endif
                <div>
                    <label for="cinema-name" class="{{ $labelClass }}">Cinema name</label>
                    <input id="cinema-name" name="name" value="{{ old('name', $editingCinema?->name) }}" required maxlength="120" placeholder="e.g. CineBook Downtown" class="{{ $fieldClass }}" @error('name') aria-invalid="true" aria-describedby="cinema-name-error" @enderror>
                    <x-admin.field-error field="name" id="cinema-name-error" />
                </div>
                <div>
                    <label for="cinema-address" class="{{ $labelClass }}">Address</label>
                    <textarea id="cinema-address" name="address" rows="3" maxlength="255" placeholder="Street, city, postal code" class="{{ $fieldClass }}" @error('address') aria-invalid="true" aria-describedby="cinema-address-error" @enderror>{{ old('address', $editingCinema?->address) }}</textarea>
                    <x-admin.field-error field="address" id="cinema-address-error" />
                </div>
                <div>
                    <label for="cinema-phone" class="{{ $labelClass }}">Phone number</label>
                    <input id="cinema-phone" name="phone" value="{{ old('phone', $editingCinema?->phone) }}" maxlength="30" placeholder="+1 555 0100" class="{{ $fieldClass }}" @error('phone') aria-invalid="true" aria-describedby="cinema-phone-error" @enderror>
                    <x-admin.field-error field="phone" id="cinema-phone-error" />
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    <x-admin.button type="submit">{{ $editingCinema ? 'Save changes' : 'Add cinema' }}</x-admin.button>
                    @if ($editingCinema)
                        <x-admin.button :href="route('admin.cinema.index')" variant="secondary" onclick="if (document.referrer && new URL(document.referrer, location.href).origin === location.origin) { event.preventDefault(); window.history.back(); }">Cancel</x-admin.button>
                    @endif
                </div>
            </form>
        </x-admin.card>
    </div>
</x-admin.layout>
