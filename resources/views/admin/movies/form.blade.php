<x-admin.layout :title="$movie->exists ? 'Edit movie' : 'Add movie'" breadcrumb="Catalog">
    <x-admin.page-header :title="$movie->exists ? 'Edit movie' : 'Add a movie'" subtitle="Movie data is saved to your catalog and used by showtime scheduling.">
        <x-slot:actions><x-admin.button variant="secondary" href="{{ route('admin.movies.index') }}">Back to movies</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />

    <x-admin.card>
        <form method="POST" enctype="multipart/form-data" action="{{ $movie->exists ? route('admin.movies.update', $movie) : route('admin.movies.store') }}" x-data="{ saving: false }" x-on:submit="if (saving) { $event.preventDefault(); return; } saving = true">
            @csrf
            @if ($movie->exists) @method('PUT') @endif
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="title" label="Title" :value="$movie->title" required />
                <x-admin.select name="status" label="Status" :value="$movie->status?->value ?? 'coming_soon'" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => str($status->value)->replace('_', ' ')->title()])->all()" required />
                <x-admin.input name="duration_minutes" label="Runtime (minutes)" type="number" min="1" max="65535" :value="$movie->duration_minutes" required />
                <x-admin.input name="rating" label="Rating" :value="$movie->rating" placeholder="PG-13" />
                <x-admin.date name="release_date" label="Release date" :value="$movie->release_date?->format('Y-m-d')" />
                <x-admin.input name="trailer_url" label="Trailer URL" type="url" :value="$movie->trailer_url" />
                <div class="md:col-span-2"><x-admin.textarea name="synopsis" label="Synopsis" :value="$movie->synopsis" required rows="5" /></div>
                <div class="md:col-span-2"><x-admin.multiselect name="genre_ids" label="Genres (Ctrl/Cmd to select multiple)" :options="$genres" :selected="$movie->genres->pluck('id')->all()" /></div>
                <div><x-admin.image-upload :value="$movie->poster_path" /></div>
                @if ($movie->poster_path)
                    <div class="flex items-end"><x-admin.toggle name="remove_poster" label="Remove current poster" /></div>
                @endif
            </div>
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <x-admin.button variant="secondary" href="{{ route('admin.movies.index') }}">Cancel</x-admin.button>
                <x-admin.button type="submit" x-bind:disabled="saving">
                    <span x-show="! saving">{{ $movie->exists ? 'Save changes' : 'Create movie' }}</span>
                    <span x-cloak x-show="saving" class="inline-flex items-center gap-2"><span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent"></span>Saving…</span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.card>
</x-admin.layout>
