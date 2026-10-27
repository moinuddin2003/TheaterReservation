<x-admin.layout :title="$customer->name" :breadcrumb="'CineBook / People / Customers / '.$customer->name">
    <x-admin.page-header
        :title="$customer->name"
        :back-url="route('admin.customers.index')"
        back-label="Back to customers"
    >
        <x-slot:meta>
            <div class="flex flex-wrap items-center gap-2">
                <x-admin.badge status="info">Customer</x-admin.badge>
                <x-admin.badge :status="$customer->email_verified_at ? 'success' : 'warning'">
                    {{ $customer->email_verified_at ? 'Email verified' : 'Email unverified' }}
                </x-admin.badge>
                <span class="text-[11px] text-cine-tertiary">Member since {{ $customer->created_at->format('M Y') }}</span>
            </div>
        </x-slot:meta>
    </x-admin.page-header>

    <div class="grid items-start gap-4 xl:grid-cols-[minmax(230px,0.78fr)_minmax(0,1.72fr)]">
        <div class="space-y-3">
            <x-admin.card class="!p-4 sm:!p-5">
                <div class="flex items-center gap-3 border-b border-cine-border/10 pb-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
                        <x-admin.icon name="users" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-bold text-cine-primary">{{ $customer->name }}</h3>
                        <p class="mt-1 truncate text-[10px] text-cine-tertiary">{{ $customer->email }}</p>
                    </div>
                </div>
                <dl class="divide-y divide-cine-border/10">
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-[10px] text-cine-tertiary">Member since</dt>
                        <dd class="text-right text-[10px] font-semibold text-cine-primary">{{ $customer->created_at->format('M j, Y') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-[10px] text-cine-tertiary">Email verified</dt>
                        <dd class="text-right text-[10px] font-semibold text-cine-primary">{{ $customer->email_verified_at ? 'Yes · '.$customer->email_verified_at->format('M j, Y') : 'No' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 pt-2.5">
                        <dt class="text-[10px] text-cine-tertiary">Role</dt>
                        <dd class="text-right text-[10px] font-semibold text-cine-primary">Customer</dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card class="!p-4 sm:!p-5">
                <h3 class="text-sm font-bold text-cine-primary">Spending summary</h3>
                <dl class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-cine-border/10 bg-cine-bg p-3">
                        <dt class="text-[10px] text-cine-tertiary">Bookings</dt>
                        <dd class="mt-1 text-base font-bold text-cine-primary">{{ number_format($customer->bookings_count) }}</dd>
                    </div>
                    <div class="rounded-xl border border-cine-border/10 bg-cine-bg p-3">
                        <dt class="text-[10px] text-cine-tertiary">Total spent (net)</dt>
                        <dd class="mt-1 text-base font-bold text-cine-accent">
                            <x-admin.money :cents="(int) $customer->total_spent_cents" currency="USD" />
                        </dd>
                    </div>
                    <div class="rounded-xl border border-cine-border/10 bg-cine-bg p-3">
                        <dt class="text-[10px] text-cine-tertiary">Avg. ticket price</dt>
                        <dd class="mt-1 text-base font-bold text-cine-primary">
                            <x-admin.money :cents="$averageTicketPriceCents" currency="USD" />
                        </dd>
                    </div>
                    <div class="rounded-xl border border-cine-border/10 bg-cine-bg p-3">
                        <dt class="text-[10px] text-cine-tertiary">Favourite genre</dt>
                        <dd class="mt-1 truncate text-base font-bold text-cine-primary">
                            {{ $favoriteGenre }}
                        </dd>
                    </div>
                </dl>
            </x-admin.card>

            <div class="flex items-start gap-2 rounded-xl border border-cine-accent/20 bg-cine-accent/10 px-3 py-2.5">
                <x-admin.icon name="lock" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-cine-accent" />
                <p class="text-[10px] leading-4 text-cine-secondary">Customer details are read-only for admins. Account changes are managed by the customer.</p>
            </div>
        </div>

        <x-admin.card class="!p-0">
            <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-4">
                <h3 class="text-sm font-bold text-cine-primary">
                    Booking history
                    <span class="ml-1 rounded-full bg-cine-elevated px-2 py-0.5 text-[10px] font-semibold text-cine-secondary">{{ number_format($bookings->total()) }}</span>
                </h3>
                <form method="GET" action="{{ route('admin.customers.show', $customer) }}" class="flex flex-wrap gap-2">
                    <label class="sr-only" for="booking-status">Booking status</label>
                    <select id="booking-status" name="status" x-on:change="$el.form.requestSubmit()" class="min-h-8 rounded-lg border border-cine-border/15 bg-cine-bg px-2.5 text-[10px] text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                        <option value="">All statuses</option>
                        @foreach (\App\Enums\BookingStatus::cases() as $bookingStatus)
                            <option value="{{ $bookingStatus->value }}" @selected($filters['status'] === $bookingStatus->value)>
                                {{ str($bookingStatus->value)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="booking-sort">Sort bookings</label>
                    <select id="booking-sort" name="sort" x-on:change="$el.form.requestSubmit()" class="min-h-8 rounded-lg border border-cine-border/15 bg-cine-bg px-2.5 text-[10px] text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                        <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                    </select>
                </form>
            </div>

            @if ($bookings->isEmpty())
                <div class="p-8">
                    <x-admin.empty-state icon="ticket" title="No bookings found" text="Try changing the status filter." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="admin-data-table w-full min-w-[650px] table-fixed divide-y divide-cine-border/10">
                        <colgroup>
                            <col class="w-[37%]">
                            <col class="w-[25%]">
                            <col class="w-[15%]">
                            <col class="w-[23%]">
                        </colgroup>
                        <thead class="bg-cine-elevated/40">
                            <tr class="text-left text-[9px] font-bold uppercase tracking-wider text-cine-tertiary">
                                <th scope="col" class="px-2.5 py-3">Booking / movie</th>
                                <th scope="col" class="px-2.5 py-3">Showtime</th>
                                <th scope="col" class="px-2.5 py-3">Amount</th>
                                <th scope="col" class="px-2.5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cine-border/10">
                            @foreach ($bookings as $booking)
                                @php
                                    $statusStyle = match ($booking->status->value) {
                                        'confirmed' => 'success',
                                        'cancelled', 'failed' => 'danger',
                                        'pending_payment' => 'warning',
                                        default => 'info',
                                    };
                                @endphp
                                <tr class="transition hover:bg-cine-elevated/30">
                                    <td class="min-w-0 px-2.5 py-2.5">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="group flex min-w-0 items-center gap-2.5 rounded-md text-left">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-cine-border/10 bg-cine-accent/10 text-cine-accent">
                                                <x-admin.icon name="film" class="h-4 w-4" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-[9px] font-semibold text-cine-accent transition group-hover:text-cine-primary">{{ $booking->reference }}</span>
                                                <span class="mt-0.5 block truncate text-[11px] font-semibold text-cine-primary">{{ $booking->showtime->movie->title }}</span>
                                                <span class="mt-0.5 block truncate text-[9px] text-cine-tertiary">
                                                    {{ $booking->items_count }} seat{{ $booking->items_count === 1 ? '' : 's' }}{{ $booking->items->isNotEmpty() ? ' · '.$booking->items->pluck('seat_label')->implode(', ') : '' }}
                                                </span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="px-2.5 py-2.5">
                                        <span class="block truncate text-[10px] font-semibold text-cine-primary">{{ $booking->showtime->starts_at->format('D j M Y') }}</span>
                                        <span class="mt-1 block truncate text-[9px] text-cine-tertiary">{{ $booking->showtime->starts_at->format('H:i') }} · {{ $booking->showtime->screen->name }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-2.5 py-2.5 text-[10px] font-semibold text-cine-primary">
                                        <x-admin.money :cents="$booking->total_cents" :currency="$booking->currency" />
                                    </td>
                                    <td class="whitespace-nowrap px-2.5 py-2.5">
                                        <x-admin.badge :status="$statusStyle">
                                            <span class="text-[9px]">{{ str($booking->status->value)->replace('_', ' ')->title() }}</span>
                                        </x-admin.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[11px] text-cine-tertiary">
                        Showing {{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} of {{ number_format($bookings->total()) }} bookings
                    </p>
                    <div>{{ $bookings->onEachSide(1)->links('vendor.pagination.admin') }}</div>
                </div>
            @endif
        </x-admin.card>
    </div>
</x-admin.layout>
