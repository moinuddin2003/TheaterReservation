@php($editing = $showtime !== null)
@php($fieldClass = 'min-h-11 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20')
@php($labelClass = 'mb-2 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary')

<x-admin.layout :title="$editing ? 'Edit showtime' : 'Create showtime'" breadcrumb="CineBook / Admin / Showtime">
    <x-admin.page-header
        :title="$editing ? 'Edit showtime' : 'Create showtime'"
        :subtitle="$editing ? 'Update the movie, screen, time and ticket price.' : 'Schedule a movie in an active cinema screen.'"
        :back-url="route('admin.showtimes.index')"
        back-label="Back to schedule"
    >
    </x-admin.page-header>

    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">
            <p class="font-semibold">Please correct the highlighted fields.</p>
        </div>
    @endif

    <div class="max-w-3xl">
        <x-admin.card title="Showtime details">
            <form method="POST" action="{{ $editing ? route('admin.showtimes.update', $showtime) : route('admin.showtimes.store') }}" class="space-y-5" novalidate>
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div>
                    <label for="movie_id" class="{{ $labelClass }}">Movie</label>
                    <select id="movie_id" name="movie_id" required class="{{ $fieldClass }}" @error('movie_id') aria-invalid="true" aria-describedby="showtime-movie-error" @enderror>
                        <option value="">Choose a movie</option>
                        @foreach ($movies as $movie)
                            <option value="{{ $movie->id }}" @selected((string) old('movie_id', $showtime?->movie_id) === (string) $movie->id)>{{ $movie->title }}</option>
                        @endforeach
                    </select>
                    <x-admin.field-error field="movie_id" id="showtime-movie-error" />
                </div>

                <div>
                    <label for="screen_id" class="{{ $labelClass }}">Cinema and screen</label>
                    <select id="screen_id" name="screen_id" required class="{{ $fieldClass }}" @error('screen_id') aria-invalid="true" aria-describedby="showtime-screen-error" @enderror>
                        <option value="">Choose a screen</option>
                        @foreach ($screens as $screen)
                            <option value="{{ $screen->id }}" @selected((string) old('screen_id', $showtime?->screen_id) === (string) $screen->id)>{{ $screen->cinema->name }} · {{ $screen->name }}</option>
                        @endforeach
                    </select>
                    <x-admin.field-error field="screen_id" id="showtime-screen-error" />
                    @if ($screens->isEmpty())
                        <p class="mt-2 text-xs text-cine-warning">Add an active screen before scheduling a showtime.</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="starts_at" class="{{ $labelClass }}">Start date and time</label>
                        <input id="starts_at" name="starts_at" type="datetime-local" required value="{{ old('starts_at', $showtime?->starts_at?->format('Y-m-d\TH:i') ?? ($startDate !== null ? $startDate.'T09:00' : '')) }}" class="{{ $fieldClass }}" @error('starts_at') aria-invalid="true" aria-describedby="showtime-start-error" @enderror>
                        <x-admin.field-error field="starts_at" id="showtime-start-error" />
                    </div>
                    <div>
                        <label for="base_price" class="{{ $labelClass }}">Ticket price (USD)</label>
                        <input id="base_price" name="base_price" type="number" min="0.50" max="1000" step="0.01" required value="{{ old('base_price', $showtime ? number_format($showtime->base_price_cents / 100, 2, '.', '') : '') }}" placeholder="12.00" class="{{ $fieldClass }}" @error('base_price') aria-invalid="true" aria-describedby="showtime-price-error" @enderror>
                        <x-admin.field-error field="base_price" id="showtime-price-error" />
                    </div>
                </div>

                <p class="rounded-xl border border-cine-accent/15 bg-cine-accent/5 p-3 text-xs leading-5 text-cine-secondary">
                    End time is calculated from the selected movie runtime and the configured screen turnaround time. Overlapping showtimes on the same screen are not allowed.
                </p>

                <div class="flex flex-wrap gap-2 border-t border-cine-border/10 pt-5">
                    <x-admin.button type="submit">{{ $editing ? 'Save changes' : 'Create showtime' }}</x-admin.button>
                    <x-admin.button :href="route('admin.showtimes.index')" variant="secondary">Cancel</x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </div>
</x-admin.layout>
