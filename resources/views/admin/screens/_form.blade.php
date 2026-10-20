@php
    $fieldClass = 'min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20';
    $labelClass = 'mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary';
@endphp

<x-admin.page-header :title="$formTitle" :subtitle="$formSubtitle" />

@if ($cinemas->isEmpty())
    <x-admin.card>
        <x-admin.empty-state icon="building" title="Create a cinema first" text="A screen must belong to a cinema location. Add a cinema before creating an auditorium.">
        </x-admin.empty-state>
        <div class="-mt-5 pb-5 text-center"><x-admin.button :href="route('admin.cinema.index')">Go to cinemas</x-admin.button></div>
    </x-admin.card>
@else
    <div class="max-w-3xl">
        <x-admin.card>
            <form method="POST" action="{{ $screen ? route('admin.screens.update', $screen) : route('admin.screens.store') }}" class="space-y-5">
                @csrf
                @if ($screen)
                    @method('PUT')
                @endif
                <div>
                    <label for="cinema_id" class="{{ $labelClass }}">Cinema</label>
                    <select id="cinema_id" name="cinema_id" required class="{{ $fieldClass }}">
                        <option value="">Select cinema</option>
                        @foreach ($cinemas as $cinema)
                            <option value="{{ $cinema->id }}" @selected((string) old('cinema_id', $screen?->cinema_id ?? request('cinema_id')) === (string) $cinema->id)>{{ $cinema->name }}</option>
                        @endforeach
                    </select>
                    @error('cinema_id')<p class="mt-1 text-xs text-cine-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="{{ $labelClass }}">Screen name</label>
                    <input id="name" name="name" value="{{ old('name', $screen?->name) }}" required maxlength="60" placeholder="e.g. Screen 1" class="{{ $fieldClass }}">
                    @error('name')<p class="mt-1 text-xs text-cine-danger">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="rows_count" class="{{ $labelClass }}">Rows</label>
                        <input id="rows_count" name="rows_count" type="number" min="1" max="26" value="{{ old('rows_count', $screen?->rows_count ?? 8) }}" required class="{{ $fieldClass }}">
                        @error('rows_count')<p class="mt-1 text-xs text-cine-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="seats_per_row" class="{{ $labelClass }}">Seats per row</label>
                        <input id="seats_per_row" name="seats_per_row" type="number" min="1" max="50" value="{{ old('seats_per_row', $screen?->seats_per_row ?? 12) }}" required class="{{ $fieldClass }}">
                        @error('seats_per_row')<p class="mt-1 text-xs text-cine-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
                <label class="flex items-center gap-3 rounded-xl border border-cine-border/10 bg-cine-bg/50 p-4">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $screen?->is_active ?? true)) class="rounded border-cine-border/20 bg-cine-bg text-cine-accent focus:ring-cine-accent">
                    <span><span class="block text-sm font-semibold text-cine-primary">Screen is active</span><span class="mt-0.5 block text-xs text-cine-secondary">Inactive screens are not available for scheduling.</span></span>
                </label>
                @error('is_active')<p class="text-xs text-cine-danger">{{ $message }}</p>@enderror

                <div class="flex flex-wrap gap-2 border-t border-cine-border/10 pt-5">
                    <x-admin.button type="submit">{{ $screen ? 'Save changes' : 'Create screen' }}</x-admin.button>
                    <x-admin.button :href="$screen ? route('admin.screens.show', $screen) : route('admin.cinema.index')" variant="secondary" onclick="if (document.referrer && new URL(document.referrer, location.href).origin === location.origin) { event.preventDefault(); window.history.back(); }">Cancel</x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </div>
@endif
