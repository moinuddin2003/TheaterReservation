<x-admin.layout title="Showtimes" breadcrumb="CineBook / Showtime">
    <x-admin.page-header
        title="The schedule"
        subtitle="Manage movie showtimes, screening halls and schedule details."
    >
        <x-slot:actions>
            <form method="GET" action="{{ route('admin.showtimes.index') }}" class="flex items-center gap-2 rounded-full border border-cine-border/10 bg-cine-surface p-1.5">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <input type="hidden" name="cinema_id" value="{{ $filters['cinema_id'] }}">
                <input type="hidden" name="screen_id" value="{{ $filters['screen_id'] }}">
                <label for="schedule-date" class="flex items-center gap-2 pl-2 text-xs font-semibold text-cine-secondary">
                    <x-admin.icon name="calendar" class="h-4 w-4 text-cine-accent" />
                    <span class="sr-only">Choose schedule start date</span>
                    <span class="hidden sm:inline">Date</span>
                </label>
                <input
                    id="schedule-date"
                    type="date"
                    name="date"
                    value="{{ $selectedDate->toDateString() }}"
                    class="min-h-10 w-36 rounded-full border-0 bg-transparent px-2 text-xs font-semibold text-cine-primary focus:outline-none focus:ring-2 focus:ring-cine-accent/40"
                    onchange="this.form.submit()"
                >
            </form>
            <a href="{{ route('admin.showtimes.index', array_merge(request()->except('page'), ['date' => now($timezone)->toDateString()])) }}" class="rounded-full border border-cine-border/10 bg-cine-surface px-4 py-2.5 text-xs font-semibold text-cine-secondary transition hover:border-cine-accent/30 hover:bg-cine-elevated hover:text-cine-primary">Today</a>
            <x-admin.button :href="route('admin.showtimes.create')">Create showtime</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ session('error') }}</div>
    @endif

    <section class="mb-4 overflow-hidden rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg shadow-cine-card">
        <div class="p-4 sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-cine-tertiary">Schedule</p>
                    <h3 class="mt-1 text-sm font-semibold text-cine-primary sm:text-base">Weekly showtimes</h3>
                </div>
                <div class="flex items-center gap-2">
                    <span class="hidden rounded-full border border-cine-accent/20 bg-cine-accent/10 px-3 py-1.5 text-[10px] font-semibold text-cine-accent sm:inline-flex">
                        Weekly view · {{ $weekStart->format('M j') }} - {{ $weekEnd->format('M j, Y') }}
                    </span>
                    <a
                        href="{{ route('admin.showtimes.index', array_merge(request()->except('page'), ['date' => $selectedDate->copy()->subWeek()->toDateString()])) }}"
                        aria-label="Previous week"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-cine-border/10 bg-cine-bg/70 text-cine-secondary transition hover:border-cine-accent/30 hover:bg-cine-elevated hover:text-cine-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cine-accent/50"
                    >
                        <x-admin.icon name="chevron-left" class="h-4 w-4" />
                    </a>
                    <a
                        href="{{ route('admin.showtimes.index', array_merge(request()->except('page'), ['date' => $selectedDate->copy()->addWeek()->toDateString()])) }}"
                        aria-label="Next week"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-cine-border/10 bg-cine-bg/70 text-cine-secondary transition hover:border-cine-accent/30 hover:bg-cine-elevated hover:text-cine-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cine-accent/50"
                    >
                        <x-admin.icon name="chevron-right" class="h-4 w-4" />
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-7">
                @foreach ($weekDays as $dayIndex => $day)
                    <a
                        href="{{ route('admin.showtimes.index', array_merge(request()->except('page'), ['date' => $day->toDateString()])) }}"
                        @class([
                            'relative flex min-h-28 flex-col rounded-2xl border p-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cine-accent/60 sm:min-h-32 sm:p-4',
                            'border-cine-accent/70 bg-gradient-to-br from-cine-selected-start to-cine-selected-end text-white shadow-cine-glow' => $day->isSameDay($selectedDate),
                            'border-cine-border/20 bg-gradient-to-br from-cine-bg/70 to-cine-deep/50 text-cine-secondary' => ! $day->isSameDay($selectedDate),
                        ])
                        @if ($day->isSameDay($selectedDate)) aria-current="date" @endif
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-[9px] font-bold uppercase tracking-[0.12em] {{ $day->isSameDay($selectedDate) ? 'text-white/80' : 'text-cine-tertiary' }}">{{ $day->format('D') }}</span>
                            @if ($day->isSameDay(now($timezone)->startOfDay()))
                                <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-1.5 py-0.5 text-[8px] font-bold text-white">
                                    <x-admin.icon name="calendar" class="h-2.5 w-2.5" />
                                    Today
                                </span>
                            @endif
                        </div>
                        <span class="mt-1.5 text-2xl font-bold leading-none tracking-tight sm:text-3xl">{{ $day->format('d') }}</span>
                        <span class="mt-1 text-[11px] font-medium {{ $day->isSameDay($selectedDate) ? 'text-white/75' : 'text-cine-tertiary' }}">{{ $day->format('M') }}</span>
                        <span @class([
                            'mt-2 inline-flex w-fit items-center gap-1 rounded-full px-2 py-1 text-[9px] font-semibold',
                            'bg-white/15 text-white' => $day->isSameDay($selectedDate),
                            'border border-cine-border/10 bg-cine-elevated/70 text-cine-secondary' => ! $day->isSameDay($selectedDate),
                        ])>
                            <x-admin.icon name="calendar" class="h-3 w-3" />
                            {{ $showtimeCounts->get($dayIndex, 0) }} {{ (int) $showtimeCounts->get($dayIndex, 0) === 1 ? 'showtime' : 'showtimes' }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <form method="GET" action="{{ route('admin.showtimes.index') }}" class="grid gap-3 border-t border-cine-border/10 bg-cine-bg/20 p-4 sm:grid-cols-2 lg:grid-cols-[minmax(16rem,1fr)_minmax(12rem,0.55fr)_minmax(12rem,0.65fr)_auto_auto] lg:items-center">
            <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
            <label class="relative min-w-0">
                <span class="sr-only">Search movie, cinema or screen</span>
                <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-secondary" />
                <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search movie, cinema or screen..." class="min-h-10 w-full rounded-xl border border-cine-border/20 bg-cine-bg/70 pl-10 pr-3 text-sm text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
            </label>
            <label class="relative">
                <span class="sr-only">Filter by cinema</span>
                <x-admin.icon name="cinema" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-secondary" />
                <select name="cinema_id" class="min-h-10 w-full rounded-xl border border-cine-border/20 bg-cine-bg/70 pl-10 pr-8 text-sm text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    <option value="">All cinemas</option>
                    @foreach ($cinemas as $cinema)
                        <option value="{{ $cinema->id }}" @selected($filters['cinema_id'] === $cinema->id)>{{ $cinema->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="relative">
                <span class="sr-only">Filter by screen</span>
                <x-admin.icon name="monitor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-secondary" />
                <select name="screen_id" class="min-h-10 w-full rounded-xl border border-cine-border/20 bg-cine-bg/70 pl-10 pr-8 text-sm text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    <option value="">All screens</option>
                    @foreach ($screens as $screen)
                        <option value="{{ $screen->id }}" @selected($filters['screen_id'] === $screen->id)>{{ $screen->name }} · {{ $screen->cinema->name }}</option>
                    @endforeach
                </select>
            </label>
            <x-admin.button type="submit" class="min-h-10 justify-center rounded-xl px-5">
                <x-admin.icon name="filter" class="h-4 w-4" />
                Filter
            </x-admin.button>
            <a href="{{ route('admin.showtimes.index', ['date' => $selectedDate->toDateString()]) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-cine-border/20 bg-cine-surface px-4 text-xs font-semibold text-cine-secondary transition hover:border-cine-accent/30 hover:bg-cine-elevated hover:text-cine-primary">
                <x-admin.icon name="undo-2" class="h-4 w-4" />
                Reset
            </a>
        </form>
    </section>

    <x-admin.card class="!p-0">
        @if ($showtimes->isEmpty())
            <div class="flex flex-col items-center gap-3 p-8 text-center">
                <x-admin.empty-state
                    icon="calendar-clock"
                    title="No showtimes on {{ $selectedDate->format('D j M') }}"
                    text="There are no showtimes from this date onward for the current filters."
                />
                <x-admin.button :href="route('admin.showtimes.create', ['date' => $selectedDate->toDateString()])">Create showtime</x-admin.button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-cine-border/10">
                    <thead class="bg-cine-bg/40">
                        <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-cine-tertiary">
                            <th scope="col" class="px-4 py-3 sm:px-5">#</th>
                            <th scope="col" class="px-3 py-3 sm:px-4">Movie</th>
                            <th scope="col" class="px-3 py-3">Cinema</th>
                            <th scope="col" class="px-3 py-3">Screen</th>
                            <th scope="col" class="px-3 py-3">Showtime</th>
                            <th scope="col" class="px-3 py-3">Price</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($showtimes as $showtime)
                            @php
                                $startsAtLocal = $showtime->starts_at->copy()->setTimezone($timezone);
                                $runtimeEndsAtLocal = $startsAtLocal->copy()->addMinutes($showtime->movie->duration_minutes);
                                $displayEndsAtLocal = $showtime->ends_at->copy()->setTimezone($timezone);
                                $statusLabel = match (true) {
                                    $showtime->isCancelled() => 'Cancelled',
                                    $now->lt($startsAtLocal) => 'Scheduled',
                                    $now->lt($runtimeEndsAtLocal) => 'Now Showing',
                                    default => 'Completed',
                                };
                                $statusStyle = match ($statusLabel) {
                                    'Cancelled' => 'danger',
                                    'Completed' => 'warning',
                                    'Now Showing' => 'success',
                                    default => 'info',
                                };
                            @endphp
                            <tr class="text-xs transition-colors hover:bg-cine-elevated/40">
                                <td class="whitespace-nowrap px-3 py-3 text-cine-tertiary sm:px-4">{{ $showtimes->firstItem() + $loop->index }}</td>
                                <td class="min-w-48 px-3 py-2.5">
                                    <div class="flex items-center gap-3">
                                        @if ($showtime->movie->posterUrl())
                                            <img src="{{ $showtime->movie->posterUrl() }}" alt="" loading="lazy" class="h-11 w-8 shrink-0 rounded-md border border-cine-border/10 object-cover">
                                        @else
                                            <span class="flex h-11 w-8 shrink-0 items-center justify-center rounded-md border border-cine-border/10 bg-cine-elevated text-cine-tertiary"><x-admin.icon name="film" class="h-4 w-4" /></span>
                                        @endif
                                        <span class="font-semibold leading-5 text-cine-primary">{{ $showtime->movie->title }}</span>
                                    </div>
                                </td>
                                <td class="min-w-36 px-3 py-2.5">
                                    <span class="block font-medium text-cine-primary">{{ $showtime->screen->cinema->name }}</span>
                                    <span class="mt-0.5 block text-[10px] text-cine-tertiary">{{ $showtime->screen->cinema->phone ?: 'Cinema' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5">
                                    <span class="block font-medium text-cine-primary">{{ $showtime->screen->name }}</span>
                                    <span class="mt-0.5 block text-[10px] text-cine-tertiary">{{ number_format($showtime->screen->rows_count * $showtime->screen->seats_per_row) }} seats</span>
                                </td>
                                <td class="min-w-36 whitespace-nowrap px-3 py-2.5 text-cine-secondary">
                                    <span class="block">{{ $startsAtLocal->format('M j, Y') }}</span>
                                    <span class="mt-0.5 block font-semibold text-cine-primary">{{ $startsAtLocal->format('h:i A') }}–{{ $displayEndsAtLocal->format('h:i A') }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 font-semibold text-cine-primary"><x-admin.money :cents="$showtime->base_price_cents" /></td>
                                <td class="whitespace-nowrap px-3 py-2.5"><x-admin.badge :status="$statusStyle">{{ $statusLabel }}</x-admin.badge></td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right">
                                    <x-admin.showtime-actions :showtime="$showtime" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 text-xs text-cine-tertiary sm:flex-row sm:items-center sm:justify-between">
                <p>Showing {{ $showtimes->firstItem() }}–{{ $showtimes->lastItem() }} of {{ $showtimes->total() }} showtimes</p>
                <div>{{ $showtimes->onEachSide(1)->links('vendor.pagination.admin') }}</div>
            </div>
        @endif
    </x-admin.card>

</x-admin.layout>
