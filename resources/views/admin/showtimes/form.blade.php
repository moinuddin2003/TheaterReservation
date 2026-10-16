<x-admin.layout :title="$showtime->exists ? 'Edit showtime' : 'Schedule showtime'" breadcrumb="Cinema">
    <x-admin.page-header :title="$showtime->exists ? 'Edit showtime' : 'Schedule a showtime'" subtitle="The end time is calculated from the selected movie runtime plus the turnover buffer.">
        <x-slot:actions><x-admin.button variant="secondary" href="{{ route('admin.showtimes.index') }}">Back to schedule</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />
    <x-admin.card>
        <form method="POST" action="{{ $showtime->exists ? route('admin.showtimes.update', $showtime) : route('admin.showtimes.store') }}" class="space-y-5" x-data="{ saving: false }" x-on:submit="if (saving) { $event.preventDefault(); return; } saving = true">
            @csrf @if ($showtime->exists) @method('PUT') @endif
            <x-admin.select name="movie_id" label="Movie" :value="$showtime->movie_id ?? request('movie_id')" required>
                <option value="">Choose movie</option>
                @foreach ($movies as $movie)<option value="{{ $movie->id }}" @selected(old('movie_id', $showtime->movie_id ?? request('movie_id')) == $movie->id)>{{ $movie->title }} · {{ $movie->duration_minutes }} min</option>@endforeach
            </x-admin.select>
            <x-admin.select name="screen_id" label="Screen" :value="$showtime->screen_id" required>
                <option value="">Choose screen</option>
                @foreach ($screens as $screen)<option value="{{ $screen->id }}" @selected(old('screen_id', $showtime->screen_id) == $screen->id)>{{ $screen->name }}</option>@endforeach
            </x-admin.select>
            <x-admin.time name="starts_at" label="Start date and time ({{ $timezone }})" :value="$showtime->starts_at?->timezone($timezone)->format('Y-m-d\\TH:i')" required />
            <x-admin.money-input name="base_price_cents" label="Ticket price" :cents="$showtime->base_price_cents ?? 1500" required />
            <p class="text-xs text-cine-tertiary">Screenings on the same screen cannot overlap. Runtime and a 20-minute turnover are included in the check.</p>
            <div class="flex justify-end gap-3"><x-admin.button variant="secondary" href="{{ route('admin.showtimes.index') }}">Cancel</x-admin.button><x-admin.button type="submit" x-bind:disabled="saving">{{ $showtime->exists ? 'Save changes' : 'Create showtime' }}</x-admin.button></div>
        </form>
    </x-admin.card>
</x-admin.layout>
