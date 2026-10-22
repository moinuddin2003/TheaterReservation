<x-admin.layout title="Bookings" breadcrumb="CineBook / Bookings">
    <x-admin.page-header
        title="Bookings"
        subtitle="Track every reservation, payment and ticket in one place."
    >
        <x-slot:actions>
            <a href="{{ route('admin.bookings.export', request()->except('page')) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-full border border-cine-border/15 bg-cine-surface px-4 text-xs font-semibold text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">
                <x-admin.icon name="download" class="h-4 w-4" />
                Export CSV
            </a>
            <span class="inline-flex items-center gap-2 rounded-full border border-cine-border/10 bg-cine-surface px-4 py-2 text-xs font-semibold text-cine-secondary">
                <x-admin.icon name="ticket" class="h-4 w-4 text-cine-accent" />
                {{ number_format($bookings->total()) }} total
            </span>
        </x-slot:actions>
    </x-admin.page-header>

    @php
        $summaryCards = [
            ['label' => 'Total bookings', 'value' => number_format($stats['total_bookings']), 'icon' => 'ticket', 'style' => 'text-cine-accent'],
            ['label' => 'Total revenue', 'value' => '$'.number_format($stats['total_revenue'] / 100, 2), 'icon' => 'dollar-sign', 'style' => 'text-cine-success'],
            ['label' => 'Pending refunds', 'value' => number_format($stats['pending_refunds']), 'icon' => 'undo-2', 'style' => 'text-cine-warning'],
            ['label' => 'Failed payments today', 'value' => number_format($stats['failed_payments']), 'icon' => 'credit-card', 'style' => 'text-cine-danger'],
        ];
        $statusLabels = [
            'all' => 'All',
            'confirmed' => 'Confirmed',
            'pending_payment' => 'Pending payment',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];
    @endphp

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <div class="rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-medium text-cine-secondary">{{ $card['label'] }}</p>
                    <x-admin.icon :name="$card['icon']" class="h-4 w-4 {{ $card['style'] }}" />
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight text-cine-primary">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <x-admin.card class="!p-0">
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="grid gap-3 border-b border-cine-border/10 p-4 sm:grid-cols-2 xl:grid-cols-[minmax(15rem,1fr)_minmax(10rem,0.7fr)_minmax(10rem,0.55fr)_minmax(10rem,0.55fr)_auto_auto] xl:items-end">
            <label class="relative min-w-0">
                <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Search</span>
                <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-[2.65rem] h-4 w-4 -translate-y-1/2 text-cine-tertiary" />
                <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Code, customer name or email..." class="min-h-10 w-full rounded-xl border border-cine-border/15 bg-cine-bg pl-10 pr-3 text-xs text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
            </label>
            <label>
                <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Movie</span>
                <select name="movie_id" class="min-h-10 w-full rounded-xl border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    <option value="">All movies</option>
                    @foreach ($movies as $movie)
                        <option value="{{ $movie->id }}" @selected($filters['movie_id'] === $movie->id)>{{ $movie->title }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Showtime from</span>
                <input name="from" type="date" value="{{ $filters['from'] }}" class="min-h-10 w-full rounded-xl border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
            </label>
            <label>
                <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Showtime to</span>
                <input name="to" type="date" value="{{ $filters['to'] }}" class="min-h-10 w-full rounded-xl border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
            </label>
            <x-admin.button type="submit" class="min-h-10 justify-center rounded-xl px-4">
                <x-admin.icon name="filter" class="h-4 w-4" />
                Filter
            </x-admin.button>
            <a href="{{ route('admin.bookings.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-cine-border/15 px-4 text-xs font-semibold text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">Clear</a>
        </form>

        <nav aria-label="Filter bookings by status" class="flex gap-5 overflow-x-auto border-b border-cine-border/10 px-4 pt-3">
            @foreach ($statusLabels as $status => $label)
                <a
                    href="{{ route('admin.bookings.index', array_merge(request()->except('status', 'page'), $status === 'all' ? [] : ['status' => $status])) }}"
                    @class([
                        'inline-flex shrink-0 items-center gap-2 border-b-2 px-0.5 pb-3 text-xs font-semibold transition-colors',
                        'border-cine-accent text-cine-primary' => ($filters['status'] ?? 'all') === $status,
                        'border-transparent text-cine-tertiary hover:text-cine-secondary' => ($filters['status'] ?? 'all') !== $status,
                    ])
                    @if (($filters['status'] ?? 'all') === $status) aria-current="page" @endif
                >
                    {{ $label }}
                    <span class="rounded-full bg-cine-elevated px-1.5 py-0.5 text-[9px] text-cine-tertiary">{{ $status === 'all' ? number_format($statusCounts->sum()) : number_format($statusCounts->get($status, 0)) }}</span>
                </a>
            @endforeach
        </nav>

        @if ($bookings->isEmpty())
            <div class="p-8">
                <x-admin.empty-state icon="ticket" title="No bookings found" text="Try changing your search or filters." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-cine-border/10">
                    <thead class="bg-cine-bg/40">
                        <tr class="text-left text-[9px] font-bold uppercase tracking-wider text-cine-tertiary">
                            <th scope="col" class="px-4 py-3">Code</th>
                            <th scope="col" class="px-3 py-3">Customer</th>
                            <th scope="col" class="px-3 py-3">Movie &amp; showtime</th>
                            <th scope="col" class="px-3 py-3">Seats</th>
                            <th scope="col" class="px-3 py-3 text-right">Total</th>
                            <th scope="col" class="px-3 py-3">Payment</th>
                            <th scope="col" class="px-3 py-3">Booking</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($bookings as $booking)
                            @php
                                $latestPayment = $booking->payments->sortByDesc('created_at')->first();
                                $bookingStatus = $booking->status->value;
                                $bookingStyle = match ($bookingStatus) {
                                    'confirmed' => 'success',
                                    'cancelled', 'failed' => 'danger',
                                    'pending_payment' => 'warning',
                                    default => 'info',
                                };
                                $paymentStyle = match ($latestPayment?->status?->value) {
                                    'succeeded' => 'success',
                                    'failed', 'expired' => 'danger',
                                    'refunded' => 'warning',
                                    default => 'info',
                                };
                            @endphp
                            <tr class="text-xs transition-colors hover:bg-cine-elevated/30">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ route('admin.bookings.show', array_merge(request()->except('page'), ['booking' => $booking])) }}" class="font-bold text-cine-accent hover:text-cine-primary">{{ $booking->reference }}</a>
                                </td>
                                <td class="min-w-40 px-3 py-3">
                                    <span class="block font-semibold text-cine-primary">{{ $booking->user->name }}</span>
                                    <span class="mt-0.5 block text-[10px] text-cine-tertiary">{{ $booking->user->email }}</span>
                                </td>
                                <td class="min-w-48 px-3 py-3">
                                    <span class="block font-semibold text-cine-primary">{{ $booking->showtime->movie->title }}</span>
                                    <span class="mt-0.5 block text-[10px] text-cine-tertiary">{{ $booking->showtime->starts_at->setTimezone($timezone)->format('D j M · H:i') }} · {{ $booking->showtime->screen->name }}</span>
                                </td>
                                <td class="min-w-24 px-3 py-3">
                                    <div class="flex max-w-28 flex-wrap gap-1">
                                        @foreach ($booking->items->take(4) as $item)
                                            <span class="rounded-md border border-cine-border/15 bg-cine-elevated px-1.5 py-0.5 text-[9px] text-cine-secondary">{{ $item->seat_label }}</span>
                                        @endforeach
                                        @if ($booking->items_count > 4)
                                            <span class="text-[9px] text-cine-tertiary">+{{ $booking->items_count - 4 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-cine-primary">{{ $booking->currency }} {{ number_format($booking->total_cents / 100, 2) }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <x-admin.badge :status="$paymentStyle">{{ $latestPayment ? str($latestPayment->status->value)->replace('_', ' ')->title() : 'Awaiting' }}</x-admin.badge>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <x-admin.badge :status="$bookingStyle">{{ str($bookingStatus)->replace('_', ' ')->title() }}</x-admin.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 text-xs text-cine-tertiary sm:flex-row sm:items-center sm:justify-between">
                <p>Showing {{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} of {{ number_format($bookings->total()) }} bookings</p>
                <div>{{ $bookings->onEachSide(1)->links('vendor.pagination.admin') }}</div>
            </div>
        @endif
    </x-admin.card>
</x-admin.layout>
