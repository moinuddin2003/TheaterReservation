<x-admin.layout title="Showtimes" breadcrumb="Cinema">
    <x-admin.page-header title="Showtimes" subtitle="Schedule and monitor screenings across the cinema.">
        <x-slot:actions><x-admin.button href="{{ route('admin.showtimes.create') }}">Add showtime</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />

    <x-admin.date-strip :days="$weekDays" :selected="$selectedDate" />
    <x-admin.filters-bar :action="route('admin.showtimes.index')">
        <input type="hidden" name="date" value="{{ request('date', $selectedDate->toDateString()) }}">
        <label class="text-xs font-semibold text-cine-secondary">Movie
            <select name="movie_id" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
                <option value="">All movies</option>
                @foreach ($movies as $movie)<option value="{{ $movie->id }}" @selected(request('movie_id') == $movie->id)>{{ $movie->title }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Screen
            <select name="screen_id" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
                <option value="">All screens</option>
                @foreach ($screens as $screen)<option value="{{ $screen->id }}" @selected(request('screen_id') == $screen->id)>{{ $screen->name }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Status
            <select name="status" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
                <option value="">All statuses</option>
                @foreach (['scheduled', 'cancelled', 'completed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->title() }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Sort
            <select name="sort" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
                @foreach (['starts_at' => 'Start time', 'base_price_cents' => 'Price', 'status' => 'Status'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'starts_at') === $key)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Direction
            <select name="direction" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="asc" @selected(request('direction', 'asc') === 'asc')>Ascending</option><option value="desc" @selected(request('direction') === 'desc')>Descending</option></select>
        </label>
    </x-admin.filters-bar>

    <div class="overflow-x-auto rounded-2xl border border-cine-border/10 bg-cine-surface shadow-cine-card">
        <table class="min-w-full text-left text-sm">
            <thead class="sticky top-0 bg-cine-elevated/80 text-xs uppercase text-cine-tertiary"><tr><th class="px-4 py-3">Time</th><th class="px-4 py-3">Movie</th><th class="px-4 py-3">Screen</th><th class="px-4 py-3">Occupancy</th><th class="px-4 py-3">Price</th><th class="px-4 py-3">State</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-cine-border/10">
                @forelse ($showtimes as $showtime)
                    @php($capacity = $showtime->screen->seats_count)
                    @php($occupied = min($capacity, $showtime->sold_seats_count + $showtime->held_seats_count))
                    @php($closed = $showtime->starts_at->subMinutes($salesCloseMinutes)->isPast())
                    <tr class="hover:bg-cine-elevated/30">
                        <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('admin.showtimes.show', $showtime) }}" class="font-semibold text-cine-primary hover:text-cine-accent">{{ $showtime->starts_at->timezone($timezone)->format('M j · g:i A') }}</a></td>
                        <td class="px-4 py-3 text-cine-primary">{{ $showtime->movie->title }}</td>
                        <td class="px-4 py-3 text-cine-secondary">{{ $showtime->screen->name }}</td>
                        <td class="min-w-40 px-4 py-3">
                            <div class="h-1.5 overflow-hidden rounded-full bg-cine-elevated"><div class="h-full rounded-full bg-cine-accent" style="width: {{ $capacity ? min(100, round($occupied / $capacity * 100)) : 0 }}%"></div></div>
                            <p class="mt-1 text-xs text-cine-secondary">{{ $occupied }}/{{ $capacity }} seats</p>
                        </td>
                        <td class="px-4 py-3 text-cine-primary">${{ number_format($showtime->base_price_cents / 100, 2) }}</td>
                        <td class="px-4 py-3"><x-admin.status-badge :status="$showtime->status" /> @if ($closed && $showtime->status->value === 'scheduled')<span class="ml-1 text-xs text-cine-tertiary">Sales closed</span>@elseif ($capacity > 0 && $occupied >= $capacity)<span class="ml-1 text-xs text-cine-warning">Sold out</span>@endif</td>
                        <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('admin.showtimes.show', $showtime) }}" class="font-semibold text-cine-accent">Details</a> @if ($showtime->status->value === 'scheduled')<a href="{{ route('admin.showtimes.edit', $showtime) }}" class="ml-2 text-cine-secondary hover:text-cine-primary">Edit</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12"><x-admin.empty-state title="No showtimes for this week" text="Change your filters or schedule the first screening." icon="calendar-clock" /><div class="text-center"><x-admin.button href="{{ route('admin.showtimes.create') }}">Schedule showtime</x-admin.button></div></td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-cine-border/10 px-4 py-3">{{ $showtimes->withQueryString()->links() }}</div>
    </div>
</x-admin.layout>
