<x-admin.layout title="Bookings" breadcrumb="CineBook / Bookings">
    @php
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

    <x-admin.page-header
        title="Bookings"
        subtitle="Track every reservation, payment and ticket in one place."
    >
        <x-slot:actions>
            <x-admin.filter-dropdown :action="route('admin.bookings.index')" label="filters" width="w-[min(92vw,560px)]">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <div class="grid gap-3 sm:grid-cols-2">
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
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Status</span>
                        <select name="status" class="min-h-10 w-full rounded-xl border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                            <option value="">All statuses</option>
                            @foreach ($statusLabels as $status => $label)
                                @unless ($status === 'all')
                                    <option value="{{ $status }}" @selected(($filters['status'] ?? 'all') === $status)>{{ $label }}</option>
                                @endunless
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
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <a href="{{ route('admin.bookings.index') }}" class="inline-flex min-h-9 items-center rounded-lg border border-cine-border/10 px-3 text-xs font-semibold text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">Reset</a>
                    <x-admin.button type="submit" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs">Apply filters</x-admin.button>
                </div>
            </x-admin.filter-dropdown>
            <a href="{{ route('admin.bookings.export', request()->except('page')) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-cine-accent px-4 text-xs font-semibold text-white shadow-cine-glow transition hover:brightness-110">
                <x-admin.icon name="download" class="h-4 w-4" />
                Export CSV
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @php
        $summaryCards = [
            ['label' => 'Total bookings', 'value' => number_format($stats['total_bookings']), 'icon' => 'ticket', 'style' => 'text-cine-accent', 'detail' => 'All-time reservations'],
            ['label' => 'Total revenue', 'cents' => $stats['total_revenue'], 'currency' => 'USD', 'icon' => 'dollar-sign', 'style' => 'text-cine-success', 'detail' => 'Successful payments'],
            ['label' => 'Pending refunds', 'value' => number_format($stats['pending_refunds']), 'icon' => 'undo-2', 'style' => 'text-cine-warning', 'detail' => 'Awaiting resolution'],
            ['label' => 'Failed payments today', 'value' => number_format($stats['failed_payments']), 'icon' => 'credit-card', 'style' => 'text-cine-danger', 'detail' => 'Recorded today'],
        ];
    @endphp

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <div class="min-h-[112px] rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg p-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-cine-border/10 bg-cine-elevated/70 {{ $card['style'] }}">
                        <x-admin.icon :name="$card['icon']" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-cine-secondary">{{ $card['label'] }}</p>
                        <p class="mt-0.5 text-2xl font-bold tracking-tight text-cine-primary">@if (isset($card['cents']))<x-admin.money :cents="$card['cents']" :currency="$card['currency']" />@else{{ $card['value'] }}@endif</p>
                    </div>
                </div>
                <p class="mt-3 flex min-h-4 items-center text-[11px] text-cine-tertiary">{{ $card['detail'] }}</p>
            </div>
        @endforeach
    </div>

    <x-admin.card class="!p-0">
        <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h3 class="text-sm font-bold text-cine-primary">Recent bookings</h3>
                <p class="mt-1 text-[10px] text-cine-tertiary">Reservation activity across all bookings</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] text-cine-tertiary">{{ number_format($bookings->total()) }} matching</span>
                <form method="GET" action="{{ route('admin.bookings.index') }}" class="relative w-full sm:w-80">
                <input type="hidden" name="movie_id" value="{{ $filters['movie_id'] }}">
                <input type="hidden" name="from" value="{{ $filters['from'] }}">
                <input type="hidden" name="to" value="{{ $filters['to'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-tertiary" />
                <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search bookings..." aria-label="Search bookings" class="min-h-9 w-full rounded-lg border border-cine-border/15 bg-cine-bg pl-9 pr-3 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                </form>
            </div>
        </div>
        @if ($bookings->isEmpty())
            <div class="p-8">
                <x-admin.empty-state icon="ticket" title="No bookings found" text="Try changing your search or filters." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="admin-data-table min-w-full divide-y divide-cine-border/10">
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
                                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-cine-primary"><x-admin.money :cents="$booking->total_cents" :currency="$booking->currency" /></td>
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
