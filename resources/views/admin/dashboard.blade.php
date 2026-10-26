<x-admin.layout title="Dashboard" breadcrumb="Overview">
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-cine-accent">CineBook Downtown / Overview</p>
            <h2 class="text-2xl font-bold tracking-tight text-cine-primary sm:text-3xl">
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}.
            </h2>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-cine-tertiary">
                <span>{{ $updatedAt->format('l, F j, Y') }} · Updated {{ $updatedAt->format('H:i') }}</span>
                <span class="inline-flex items-center gap-2 text-cine-success">
                    <span class="h-1.5 w-1.5 rounded-full bg-cine-success"></span>
                    Cinema online
                </span>
                <span>All amounts in USD</span>
            </div>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-cine-accent px-4 text-sm font-semibold text-white shadow-cine-glow transition hover:brightness-110">
            View bookings
            <x-admin.icon name="arrow-right" class="h-4 w-4" />
        </a>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <x-admin.card class="transition-colors hover:border-cine-accent/25">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-cine-secondary">Confirmed bookings today</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-cine-primary">{{ number_format($confirmedBookingsToday) }}</p>
                    <p class="mt-2 text-xs text-cine-tertiary">{{ number_format($ticketsSoldToday) }} tickets sold</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
                    <x-admin.icon name="ticket" class="h-5 w-5" />
                </span>
            </div>
        </x-admin.card>

        <x-admin.card class="transition-colors hover:border-cine-accent/25">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-cine-secondary">Net revenue today</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-cine-primary"><x-admin.money :cents="$netRevenueCents" /></p>
                    <p class="mt-2 text-xs text-cine-tertiary">After successful refunds</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
                    <x-admin.icon name="dollar-sign" class="h-5 w-5" />
                </span>
            </div>
        </x-admin.card>

        <x-admin.card class="transition-colors hover:border-cine-accent/25">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-cine-secondary">Next 24h occupancy</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-cine-primary">{{ number_format($occupancy, 1) }}%</p>
                    <p class="mt-2 text-xs text-cine-tertiary">{{ number_format($ticketsSold) }} sold + {{ number_format($ticketsHeld) }} held / {{ number_format($capacity) }} capacity</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
                    <x-admin.icon name="chart-no-axes-column-increasing" class="h-5 w-5" />
                </span>
            </div>
        </x-admin.card>
    </div>

    <div class="mb-7 grid gap-4 xl:grid-cols-[minmax(0,1.8fr)_minmax(250px,0.8fr)]">
        <x-admin.card class="min-w-0">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 class="font-semibold text-cine-primary">Ticket sales today</h3>
                <span class="text-[11px] text-cine-tertiary">Confirmed tickets · hourly</span>
            </div>
            <div class="overflow-hidden">
                <svg viewBox="0 0 720 180" preserveAspectRatio="none" class="h-44 w-full" role="img" aria-label="Hourly confirmed ticket sales today">
                    <defs>
                        <linearGradient id="sales-fill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="rgb(30 144 255)" stop-opacity=".24" />
                            <stop offset="100%" stop-color="rgb(30 144 255)" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    @foreach ([30, 84, 138] as $y)
                        <line x1="18" y1="{{ $y }}" x2="702" y2="{{ $y }}" stroke="currentColor" class="text-cine-border/10" stroke-dasharray="4 7" />
                    @endforeach
                    <polygon points="{{ $chartArea }}" fill="url(#sales-fill)" />
                    <polyline points="{{ $chartPoints }}" fill="none" stroke="rgb(30 144 255)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                    @foreach ($chartLabels as $label)
                        <text x="{{ $label['x'] }}" y="164" fill="currentColor" class="fill-cine-tertiary" font-size="10" text-anchor="middle">{{ $label['label'] }}</text>
                    @endforeach
                </svg>
            </div>
            <p class="mt-1 text-[11px] text-cine-tertiary">Refunded and unconfirmed bookings are excluded.</p>
        </x-admin.card>

        <x-admin.card>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="font-semibold text-cine-primary">Needs attention</h3>
                <span class="rounded-full bg-cine-warning/10 px-2 py-1 text-[10px] font-semibold text-cine-warning">Operations</span>
            </div>
            <ul class="space-y-1">
                @foreach ($attention as $item)
                    <li>
                        @if ($item['route'])
                            <a href="{{ route($item['route']) }}" class="group flex items-center justify-between gap-3 rounded-lg px-2 py-2 text-xs text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">
                                <span>{{ $item['label'] }}</span>
                                <span class="inline-flex shrink-0 items-center gap-1 font-semibold {{ $item['count'] > 0 ? 'text-cine-warning' : 'text-cine-tertiary' }}">
                                    {{ number_format($item['count']) }}
                                    <x-admin.icon name="arrow-right" class="h-3 w-3 transition group-hover:translate-x-0.5" />
                                </span>
                            </a>
                        @else
                            <div class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 text-xs text-cine-secondary">
                                <span>{{ $item['label'] }}</span>
                                <span class="font-semibold {{ $item['count'] > 0 ? 'text-cine-warning' : 'text-cine-tertiary' }}">{{ number_format($item['count']) }}</span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('admin.payments.index') }}" class="mt-4 inline-flex min-h-9 items-center gap-2 rounded-lg border border-cine-border/10 bg-cine-elevated px-3 text-xs font-semibold text-cine-primary transition hover:border-cine-accent/30">
                Review operations
                <x-admin.icon name="arrow-right" class="h-3.5 w-3.5" />
            </a>
        </x-admin.card>
    </div>

    <div class="mb-4 flex items-end justify-between gap-3">
        <div>
            <h3 class="text-lg font-bold tracking-tight text-cine-primary">Upcoming · next 24 hours</h3>
            <p class="mt-1 text-xs text-cine-tertiary">Scheduled screenings and live seat availability.</p>
        </div>
        <a href="{{ route('admin.showtimes.index') }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-cine-accent transition hover:text-cine-primary">
            All showtimes <x-admin.icon name="arrow-right" class="h-3.5 w-3.5" />
        </a>
    </div>
    <x-admin.card class="!p-0">
        @if ($showtimes->isEmpty())
            <div class="px-5 py-10 text-center">
                <x-admin.icon name="calendar-clock" class="mx-auto h-7 w-7 text-cine-tertiary" />
                <p class="mt-3 text-sm font-semibold text-cine-primary">No showtimes scheduled</p>
                <p class="mt-1 text-xs text-cine-tertiary">Upcoming screenings will appear here.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-xs">
                    <thead class="border-b border-cine-border/10 text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">
                        <tr>
                            <th scope="col" class="px-5 py-4">Movie / screen</th>
                            <th scope="col" class="px-4 py-4">Start</th>
                            <th scope="col" class="px-4 py-4 text-right">Sold</th>
                            <th scope="col" class="px-4 py-4 text-right">Held</th>
                            <th scope="col" class="px-4 py-4 text-right">Capacity</th>
                            <th scope="col" class="px-5 py-4 text-right">Occupancy</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($showtimes as $showtime)
                            @php
                                $showCapacity = $showtime->screen->capacity;
                                $showOccupancy = $showCapacity > 0
                                    ? (($showtime->sold_count + $showtime->held_count) / $showCapacity) * 100
                                    : 0;
                            @endphp
                            <tr class="transition hover:bg-cine-elevated/50">
                                <td class="px-5 py-4">
                                    <span class="block font-semibold text-cine-primary">{{ $showtime->movie->title }}</span>
                                    <span class="mt-1 block text-[11px] text-cine-tertiary">{{ $showtime->screen->name }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-cine-secondary">
                                    {{ $showtime->starts_at->isToday() ? 'Today' : 'Tomorrow' }} · {{ $showtime->starts_at->format('H:i') }}
                                </td>
                                <td class="px-4 py-4 text-right text-cine-secondary">{{ number_format($showtime->sold_count) }}</td>
                                <td class="px-4 py-4 text-right text-cine-secondary">{{ number_format($showtime->held_count) }}</td>
                                <td class="px-4 py-4 text-right text-cine-secondary">{{ number_format($showCapacity) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <span class="font-semibold text-cine-primary">{{ number_format($showOccupancy, 1) }}%</span>
                                    <span class="ml-1 text-[10px] text-cine-tertiary">{{ number_format($showtime->sold_count + $showtime->held_count) }} / {{ number_format($showCapacity) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.card>

    <div class="mt-4 flex items-start gap-2 rounded-xl border border-cine-accent/20 bg-cine-accent/10 px-4 py-3 text-xs text-cine-secondary">
        <x-admin.icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-cine-accent" />
        <p><span class="font-semibold text-cine-primary">Occupancy uses bookable capacity.</span> Sold seats and active holds are divided by non-disabled seats across scheduled showtimes in the next 24 hours.</p>
    </div>
</x-admin.layout>
