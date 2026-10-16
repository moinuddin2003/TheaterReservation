<x-admin.layout title="Movies" breadcrumb="Catalog">
    <x-admin.page-header title="Movies" subtitle="Manage your film catalog, release details, and screenings.">
        <x-slot:actions><x-admin.button href="{{ route('admin.movies.create') }}">Add movie</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />

    <x-admin.filters-bar :action="route('admin.movies.index')">
        <label class="text-xs font-semibold text-cine-secondary">Genre
            <select name="genre" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
                <option value="">All genres</option>
                @foreach ($genres as $genre)
                    <option value="{{ $genre->id }}" @selected(request('genre') == $genre->id)>{{ $genre->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Status
            <select name="status" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Sort
            <select name="sort" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
                @foreach (['created_at' => 'Recently added', 'title' => 'Title', 'release_date' => 'Release date', 'status' => 'Status'] as $key => $label)
                    <option value="{{ $key }}" @selected(request('sort', 'created_at') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">View
            <select name="view" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary" onchange="this.form.requestSubmit()">
                <option value="grid" @selected(request('view', 'grid') === 'grid')>Cards</option>
                <option value="table" @selected(request('view') === 'table')>Table</option>
            </select>
        </label>
    </x-admin.filters-bar>

    @if (request('view') === 'table')
        <div class="overflow-x-auto rounded-2xl border border-cine-border/10 bg-cine-surface">
            <table class="min-w-full text-left text-sm">
                <thead class="sticky top-0 bg-cine-elevated/80 text-xs uppercase text-cine-tertiary"><tr><th class="px-4 py-3">Movie</th><th class="px-4 py-3">Genres</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Release</th><th class="px-4 py-3">Showtimes</th><th class="px-4 py-3">Actions</th></tr></thead>
                <tbody class="divide-y divide-cine-border/10">
                    @forelse ($movies as $movie)
                        <tr class="hover:bg-cine-elevated/30">
                            <td class="px-4 py-3"><a href="{{ route('admin.movies.show', $movie) }}" class="font-semibold text-cine-primary hover:text-cine-accent">{{ $movie->title }}</a></td>
                            <td class="px-4 py-3 text-cine-secondary">{{ $movie->genres->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-4 py-3"><x-admin.status-badge :status="$movie->status" /></td>
                            <td class="px-4 py-3 text-cine-secondary">{{ $movie->release_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-cine-secondary">{{ $movie->showtimes_count }}</td>
                            <td class="px-4 py-3"><a class="font-semibold text-cine-accent" href="{{ route('admin.movies.edit', $movie) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12"><x-admin.empty-state title="No movies yet" text="Add a movie to build your catalog." /><div class="text-center"><x-admin.button href="{{ route('admin.movies.create') }}">Add a movie</x-admin.button></div></td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-cine-border/10 px-4 py-3">{{ $movies->withQueryString()->links() }}</div>
        </div>
    @else
        @if ($movies->isEmpty())
            <x-admin.card><x-admin.empty-state title="No movies yet" text="Add a movie to build your catalog and schedule showtimes." /><div class="text-center"><x-admin.button href="{{ route('admin.movies.create') }}">Add a movie</x-admin.button></div></x-admin.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($movies as $movie)
                    <article x-data="{ movieDetailsOpen: false }" class="flex min-w-0 gap-4 rounded-2xl border border-cine-border/10 bg-cine-surface p-4 shadow-cine-card">
                        <x-admin.poster :src="$movie->poster_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($movie->poster_path) : null" :title="$movie->title" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="min-w-0 flex-1 truncate text-base font-semibold text-cine-primary"><a href="{{ route('admin.movies.show', $movie) }}" class="hover:text-cine-accent">{{ $movie->title }}</a></h2>
                                <x-admin.status-badge :status="$movie->status" />
                            </div>
                            <p class="mt-2 line-clamp-2 text-xs text-cine-secondary">{{ $movie->genres->pluck('name')->join(' · ') ?: 'No genres' }}</p>
                            <p class="mt-3 text-xs text-cine-tertiary">{{ $movie->duration_minutes }} min · {{ $movie->showtimes_count }} showtimes</p>
                            <div class="mt-4 flex gap-3 text-xs font-semibold">
                                <a href="{{ route('admin.movies.edit', $movie) }}" class="text-cine-accent hover:underline">Edit</a>
                                <button type="button" x-on:click="movieDetailsOpen = true" class="text-cine-secondary hover:text-cine-primary">Details</button>
                            </div>
                        </div>
                        <x-admin.drawer name="movieDetailsOpen" :id="'movie-detail-'.$movie->id" :title="$movie->title">
                            <div class="flex gap-4">
                                <x-admin.poster :src="$movie->poster_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($movie->poster_path) : null" :title="$movie->title" />
                                <div><x-admin.status-badge :status="$movie->status" /><p class="mt-3 text-sm text-cine-secondary">{{ $movie->genres->pluck('name')->join(' · ') ?: 'No genres' }}</p><p class="mt-2 text-xs text-cine-tertiary">{{ $movie->duration_minutes }} min · {{ $movie->release_date?->format('M j, Y') ?? 'Release date TBD' }}</p></div>
                            </div>
                            <p class="mt-5 text-sm leading-6 text-cine-secondary">{{ $movie->synopsis }}</p>
                            <dl class="mt-6 grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-cine-bg p-3"><dt class="text-xs text-cine-tertiary">Upcoming screenings</dt><dd class="mt-1 text-lg font-semibold text-cine-primary">{{ $movie->upcoming_showtimes_count }}</dd></div>
                                <div class="rounded-xl bg-cine-bg p-3"><dt class="text-xs text-cine-tertiary">Tickets sold</dt><dd class="mt-1 text-lg font-semibold text-cine-primary">{{ number_format($movie->tickets_sold_count) }}</dd></div>
                            </dl>
                            <x-admin.button class="mt-6" href="{{ route('admin.movies.show', $movie) }}">Full sales detail</x-admin.button>
                        </x-admin.drawer>
                    </article>
                @endforeach
            </div>
            <div class="mt-5">{{ $movies->withQueryString()->links() }}</div>
        @endif
    @endif
</x-admin.layout>
