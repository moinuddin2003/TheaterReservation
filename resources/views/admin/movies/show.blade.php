<x-admin.layout :title="$movie->title" breadcrumb="Catalog">
    <x-admin.page-header :title="$movie->title" subtitle="Movie details, upcoming screenings, and sales activity.">
        <x-slot:actions>
            <x-admin.button variant="secondary" href="{{ route('admin.movies.edit', $movie) }}">Edit movie</x-admin.button>
            @if ($movie->status->value === 'archived')
                <form method="POST" action="{{ route('admin.movies.restore', $movie) }}">@csrf<x-admin.button type="submit">Restore</x-admin.button></form>
            @else
                <form method="POST" action="{{ route('admin.movies.archive', $movie) }}">@csrf<x-admin.button variant="secondary" type="submit">Archive</x-admin.button></form>
            @endif
        </x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <x-admin.card>
            <div class="flex flex-col gap-5 sm:flex-row">
                <x-admin.poster :src="$movie->poster_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($movie->poster_path) : null" :title="$movie->title" />
                <div class="min-w-0">
                    <x-admin.status-badge :status="$movie->status" />
                    <p class="mt-3 text-sm leading-6 text-cine-secondary">{{ $movie->synopsis }}</p>
                    <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-cine-tertiary">Runtime</dt><dd class="mt-1 text-cine-primary">{{ $movie->duration_minutes }} minutes</dd></div>
                        <div><dt class="text-xs text-cine-tertiary">Rating</dt><dd class="mt-1 text-cine-primary">{{ $movie->rating ?: 'Unrated' }}</dd></div>
                        <div><dt class="text-xs text-cine-tertiary">Release</dt><dd class="mt-1 text-cine-primary">{{ $movie->release_date?->format('M j, Y') ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-cine-tertiary">Genres</dt><dd class="mt-1 text-cine-primary">{{ $movie->genres->pluck('name')->join(', ') ?: '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        </x-admin.card>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
            <x-admin.stat-card label="Tickets sold" :value="number_format($ticketsSold)" icon="ticket" />
            <x-admin.stat-card label="Sales collected" value="${{ number_format($salesCents / 100, 2) }}" icon="dollar-sign" />
            <x-admin.stat-card label="Upcoming showtimes" :value="number_format($movie->upcoming_showtimes_count)" icon="calendar-clock" />
        </div>
    </div>
    <x-admin.card class="mt-5" title="Upcoming showtimes">
        @forelse ($upcomingShowtimes as $showtime)
            <a href="{{ route('admin.showtimes.show', $showtime) }}" class="flex items-center justify-between gap-4 border-b border-cine-border/10 py-3 last:border-0">
                <span class="text-sm font-medium text-cine-primary">{{ $showtime->starts_at->timezone($timezone)->format('M j · g:i A') }} · {{ $showtime->screen->name }}</span>
                <span class="text-xs text-cine-secondary">{{ $showtime->tickets_sold_count }} seats booked</span>
            </a>
        @empty
            <x-admin.empty-state title="No upcoming showtimes" text="Schedule a screening for this movie when ready." />
            <div class="text-center"><x-admin.button href="{{ route('admin.showtimes.create', ['movie_id' => $movie->id]) }}">Schedule a showtime</x-admin.button></div>
        @endforelse
    </x-admin.card>
    @if ($movie->showtimes_count === 0)
        <div class="mt-5 flex justify-end">
            <x-admin.confirm-dialog :action="route('admin.movies.destroy', $movie)" method="DELETE" title="Delete movie" message="Permanently delete this movie?">
                <x-admin.button variant="danger" x-on:click="open = true">Delete movie</x-admin.button>
            </x-admin.confirm-dialog>
        </div>
    @endif
</x-admin.layout>
