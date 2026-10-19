<x-admin.layout title="Movie library" breadcrumb="CineBook / Admin">
    <x-admin.page-header
        title="Movie library"
        subtitle="Manage your titles, releases, and screening status."
    >
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.movies.create') }}">
                <span aria-hidden="true">+</span>
                Add movie
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">
            {{ session('error') }}
        </div>
    @endif

    @php
        $statCards = [
            ['label' => 'Total movies', 'value' => $stats['total'], 'added' => $stats['added_this_week'], 'icon' => 'film-reel', 'accent' => 'border-cine-accent/30 bg-cine-accent/15 text-cine-accent'],
            ['label' => 'Now showing', 'value' => $stats['now_showing'], 'added' => $stats['now_showing_added'], 'icon' => 'play', 'accent' => 'border-cine-success/30 bg-cine-success/15 text-cine-success'],
            ['label' => 'Coming soon', 'value' => $stats['coming_soon'], 'added' => $stats['coming_soon_added'], 'icon' => 'calendar', 'accent' => 'border-cine-warning/30 bg-cine-warning/15 text-cine-warning'],
            ['label' => 'Archived', 'value' => $stats['archived'], 'added' => $stats['archived_added'], 'icon' => 'archive', 'accent' => 'border-cine-danger/30 bg-cine-danger/15 text-cine-danger'],
        ];
    @endphp

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $stat)
            <a
                href="{{ route('admin.movies.index', $loop->first ? [] : ['status' => ['now_showing', 'coming_soon', 'archived'][$loop->index - 1]]) }}"
                class="rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg p-4 transition hover:border-cine-accent/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cine-accent/50"
            >
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border {{ $stat['accent'] }}">
                        <x-admin.icon :name="$stat['icon']" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-cine-secondary">{{ $stat['label'] }}</p>
                        <p class="mt-0.5 text-2xl font-bold tracking-tight text-cine-primary">{{ number_format($stat['value']) }}</p>
                    </div>
                </div>
                <p class="mt-3 flex min-h-4 items-center gap-1 text-[11px] text-cine-tertiary">
                    @if ($stat['added'] > 0)
                        <x-admin.icon name="arrow-up-right" class="h-3.5 w-3.5 text-cine-success" />
                        <span><span class="font-semibold text-cine-success">+{{ number_format($stat['added']) }}</span> this week</span>
                    @else
                        <span>No new movies this week</span>
                    @endif
                </p>
            </a>
        @endforeach
    </div>

    <x-admin.card class="!p-0">
        <form method="GET" action="{{ route('admin.movies.index') }}" class="flex flex-col gap-3 border-b border-cine-border/10 p-4 lg:flex-row lg:items-center">
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Search movies by title</span>
                <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-tertiary" />
                <input
                    name="search"
                    type="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Search movies by title..."
                    class="min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg pl-10 pr-3 text-sm text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20"
                >
            </label>
            <label>
                <span class="sr-only">Filter by genre</span>
                <select name="genre" class="min-h-10 w-full min-w-32 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    <option value="">Genre</option>
                    @foreach ($genres as $genre)
                        <option value="{{ $genre->id }}" @selected($filters['genre'] === $genre->id)>{{ $genre->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Filter by status</span>
                <select name="status" class="min-h-10 w-full min-w-32 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    <option value="">Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>
                            {{ str($status->value)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
            </label>
            <input type="hidden" name="view" value="{{ $view }}">
            <x-admin.button type="submit">Filter</x-admin.button>
            <div class="flex items-center gap-1 rounded-xl border border-cine-border/10 bg-cine-bg p-1" aria-label="Movie display mode">
                <a
                    href="{{ route('admin.movies.index', request()->except('page', 'view') + ['view' => 'grid']) }}"
                    aria-label="Grid view"
                    @class(['rounded-lg p-2 transition', 'bg-cine-accent text-white shadow-cine-glow' => $view === 'grid', 'text-cine-tertiary hover:text-cine-primary' => $view !== 'grid'])
                >
                    <span class="grid h-4 w-4 grid-cols-2 gap-0.5" aria-hidden="true"><i class="rounded-[2px] bg-current"></i><i class="rounded-[2px] bg-current"></i><i class="rounded-[2px] bg-current"></i><i class="rounded-[2px] bg-current"></i></span>
                </a>
                <a
                    href="{{ route('admin.movies.index', request()->except('page', 'view') + ['view' => 'list']) }}"
                    aria-label="List view"
                    @class(['rounded-lg p-2 transition', 'bg-cine-accent text-white shadow-cine-glow' => $view === 'list', 'text-cine-tertiary hover:text-cine-primary' => $view !== 'list'])
                >
                    <span class="flex h-4 w-4 flex-col justify-center gap-1" aria-hidden="true"><i class="h-0.5 rounded bg-current"></i><i class="h-0.5 rounded bg-current"></i><i class="h-0.5 rounded bg-current"></i></span>
                </a>
            </div>
        </form>

        @if ($movies->isEmpty())
            <div class="p-5">
                <x-admin.empty-state
                    icon="clapperboard"
                    title="No movies found"
                    :text="$filters['search'] || $filters['genre'] || $filters['status'] ? 'Try changing your search or filters.' : 'Add your first movie to start building the catalog.'"
                />
            </div>
        @elseif ($view === 'list')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-cine-border/10">
                    <thead class="bg-cine-bg/40">
                        <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-cine-tertiary">
                            <th scope="col" class="px-4 py-3 sm:px-5">Movie</th>
                            <th scope="col" class="px-4 py-3">Genre</th>
                            <th scope="col" class="px-4 py-3">Duration</th>
                            <th scope="col" class="px-4 py-3">Release date</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($movies as $movie)
                            <tr class="text-xs transition-colors hover:bg-cine-elevated/40">
                                <td class="whitespace-nowrap px-4 py-2.5 sm:px-5">
                                    <div class="flex items-center gap-3">
                                        @if ($movie->posterUrl())
                                            <img src="{{ $movie->posterUrl() }}" alt="" loading="lazy" class="h-10 w-8 rounded-md border border-cine-border/10 object-cover">
                                        @else
                                            <span class="flex h-10 w-8 items-center justify-center rounded-md border border-cine-border/10 bg-cine-elevated text-cine-tertiary">
                                                <x-admin.icon name="film" class="h-4 w-4" />
                                            </span>
                                        @endif
                                        <span class="font-semibold text-cine-primary">{{ $movie->title }}</span>
                                    </div>
                                </td>
                                <td class="max-w-48 px-4 py-2.5 text-cine-secondary">
                                    {{ $movie->genres->pluck('name')->join(', ') ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-cine-secondary">
                                    {{ $movie->duration_minutes }} min{{ $movie->rating ? ' · '.$movie->rating : '' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-cine-secondary">
                                    {{ $movie->release_date?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <x-admin.badge :status="$movie->status->value === 'now_showing' ? 'success' : ($movie->status->value === 'archived' ? 'danger' : 'warning')">
                                        {{ str($movie->status->value)->replace('_', ' ')->title() }}
                                    </x-admin.badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                    <x-admin.movie-actions :movie="$movie" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($movies as $movie)
                    <article class="rounded-xl border border-cine-border/10 bg-cine-bg/40">
                        @if ($movie->posterUrl())
                            <img src="{{ $movie->posterUrl() }}" alt="" loading="lazy" class="h-48 w-full rounded-t-xl object-cover">
                        @else
                            <div class="flex h-48 items-center justify-center rounded-t-xl bg-cine-elevated text-cine-tertiary">
                                <x-admin.icon name="film" class="h-10 w-10" />
                            </div>
                        @endif
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-cine-primary">{{ $movie->title }}</h3>
                                    <p class="mt-1 text-xs text-cine-secondary">
                                        {{ $movie->genres->pluck('name')->join(', ') ?: 'No genres' }}
                                    </p>
                                </div>
                                <x-admin.badge :status="$movie->status->value === 'now_showing' ? 'success' : ($movie->status->value === 'archived' ? 'danger' : 'warning')">
                                    {{ str($movie->status->value)->replace('_', ' ')->title() }}
                                </x-admin.badge>
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t border-cine-border/10 pt-3 text-xs text-cine-tertiary">
                                <span>{{ $movie->duration_minutes }} min{{ $movie->rating ? ' · '.$movie->rating : '' }}</span>
                                <span>{{ $movie->release_date?->format('M j, Y') ?? 'No release date' }}</span>
                            </div>
                            <div class="mt-4 flex justify-end border-t border-cine-border/10 pt-3">
                                <x-admin.movie-actions :movie="$movie" />
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($movies->hasPages() || $movies->count())
            <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 text-xs text-cine-tertiary sm:flex-row sm:items-center sm:justify-between">
                <p>Showing {{ $movies->firstItem() }}–{{ $movies->lastItem() }} of {{ $movies->total() }} movies</p>
                <div>{{ $movies->onEachSide(1)->links('vendor.pagination.admin') }}</div>
            </div>
        @endif
    </x-admin.card>
</x-admin.layout>
