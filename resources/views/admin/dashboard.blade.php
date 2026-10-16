<x-admin.layout title="Dashboard" breadcrumb="Overview">
    <x-admin.page-header
        title="Good to see you, {{ auth()->user()->name }}"
        subtitle="Your CineBook control room at a glance."
    >
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.showtimes.create') }}">Schedule showtime</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($stats as $stat)
            <x-admin.stat-card :label="$stat['label']" :value="$stat['value']" :note="$stat['note']" :icon="$stat['icon']" />
        @endforeach
    </div>

    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <x-admin.card class="xl:col-span-2">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-cine-primary">Revenue · last 30 days</h2>
                    <p class="mt-1 text-xs text-cine-tertiary">Successful payments, displayed in cinema time.</p>
                </div>
            </div>
            <x-admin.line-chart :points="$revenueChart" label="Revenue over the last 30 days" />
            <div class="mt-2 flex justify-between text-[10px] text-cine-tertiary">
                <span>{{ $revenueChart->first()['day'] ?? '' }}</span>
                <span>{{ $revenueChart->last()['day'] ?? '' }}</span>
            </div>
        </x-admin.card>

        <x-admin.card>
            <h2 class="text-sm font-semibold text-cine-primary">Bookings by status</h2>
            <div class="mt-4">
                @if ($bookingStatusCounts->isNotEmpty())
                    <x-admin.donut-chart
                        :values="$bookingStatusCounts->map(fn ($count, $status) => ['label' => str_replace('_', ' ', $status), 'value' => (int) $count])->values()->all()"
                        label="Bookings by status"
                    />
                @else
                    <p class="py-8 text-center text-sm text-cine-tertiary">No bookings yet.</p>
                @endif
            </div>
        </x-admin.card>
    </div>

    <div class="mb-6 grid gap-6 xl:grid-cols-2">
        <x-admin.card>
            <h2 class="mb-4 text-sm font-semibold text-cine-primary">Today's showtimes</h2>
            <div class="space-y-4">
                @forelse ($todayShowtimes as $showtime)
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-cine-primary">{{ $showtime->movie->title }}</p>
                            <p class="mt-1 text-xs text-cine-tertiary">
                                {{ $showtime->starts_at->timezone($timezone)->format('g:i A') }}
                                · {{ $showtime->screen->name }}
                                · {{ $showtime->sold_seats_count }}/{{ $showtime->screen->seats->count() }} seats
                            </p>
                        </div>
                        <div class="w-24 shrink-0">
                            <div class="h-1.5 overflow-hidden rounded-full bg-cine-elevated">
                                <div class="h-full rounded-full bg-cine-accent" style="width: {{ $showtime->occupancy_percent }}%"></div>
                            </div>
                            <p class="mt-1 text-right text-[10px] text-cine-tertiary">{{ $showtime->occupancy_percent }}% full</p>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-cine-tertiary">No showtimes scheduled today.</p>
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card>
            <h2 class="mb-4 text-sm font-semibold text-cine-primary">Top movies</h2>
            <div class="space-y-3">
                @forelse ($topMovies as $movie)
                    <div class="flex items-center justify-between gap-3">
                        <span class="truncate text-sm text-cine-primary">{{ $movie->title }}</span>
                        <span class="shrink-0 text-xs text-cine-tertiary">{{ number_format($movie->tickets_sold) }} tickets</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-cine-tertiary">No ticket sales yet.</p>
                @endforelse
            </div>
        </x-admin.card>
    </div>

    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <x-admin.card class="min-w-0">
            <h2 class="mb-3 text-sm font-semibold text-cine-primary">Alerts</h2>
            <div class="space-y-3 text-sm">
                <p class="flex items-center justify-between text-cine-secondary">
                    <span>Failed payments (24h)</span><strong class="text-cine-primary">{{ $alerts['failed_payments'] }}</strong>
                </p>
                <p class="flex items-center justify-between text-cine-secondary">
                    <span>Failed refunds</span><strong class="text-cine-primary">{{ $alerts['failed_refunds'] }}</strong>
                </p>
                <p class="flex items-center justify-between text-cine-secondary">
                    <span>Failed webhook events</span><strong class="text-cine-primary">{{ $alerts['failed_webhooks'] }}</strong>
                </p>
            </div>
        </x-admin.card>

        <x-admin.card class="min-w-0 xl:col-span-2">
            <h2 class="mb-4 text-sm font-semibold text-cine-primary">Recent bookings</h2>
            <div class="space-y-3">
                @forelse ($recentBookings as $booking)
                    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-b border-cine-border/10 pb-3 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-cine-primary">{{ $booking->reference }} · {{ $booking->user->name }}</p>
                            <p class="mt-1 truncate text-xs text-cine-tertiary">{{ $booking->showtime->movie->title }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-cine-elevated px-2.5 py-1 text-xs text-cine-secondary">{{ str_replace('_', ' ', $booking->status->value) }}</span>
                            <span class="text-sm font-semibold text-cine-primary">${{ number_format($booking->total_cents / 100, 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-cine-tertiary">No bookings yet.</p>
                @endforelse
            </div>
        </x-admin.card>
    </div>
</x-admin.layout>
