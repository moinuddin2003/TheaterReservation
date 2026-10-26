@php
    $bookingStatus = $booking->status->value;
    $bookingStyle = match ($bookingStatus) {
        'confirmed' => 'success',
        'cancelled', 'failed' => 'danger',
        'pending_payment' => 'warning',
        default => 'info',
    };
    $paymentStyle = match ($payment?->status?->value) {
        'succeeded' => 'success',
        'failed', 'expired' => 'danger',
        'refunded' => 'warning',
        default => 'info',
    };
    $showtime = $booking->showtime;
    $startsAt = $showtime->starts_at->copy()->setTimezone($timezone);
    $endsAt = $showtime->ends_at->copy()->setTimezone($timezone);
    $movie = $showtime->movie;
    $screen = $showtime->screen;
    $cinema = $screen->cinema;
@endphp

<x-admin.layout :title="'Booking '.$booking->reference" breadcrumb="CineBook / Bookings / {{ $booking->reference }}">
    <div
        x-data="bookingActionModals"
        x-init="
            refundableCents = @js($refundableCents);
            currency = @js($booking->currency);
            stripeConfigured = @js($stripeConfigured);
            refundAmount = @js(number_format($refundableCents / 100, 2, '.', ''));
            alsoRefund = @js(! $cancellationWindowClosed);
        "
        @set-void-ticket.window="setTicket($event.detail)"
    >
    <div
        x-cloak
        x-show="toast"
        x-text="toast"
        role="status"
        aria-live="polite"
        class="fixed bottom-5 left-1/2 z-[200] w-[min(92vw,520px)] -translate-x-1/2 rounded-xl bg-cine-surface px-4 py-3 text-sm font-semibold shadow-cine-card"
        :class="toastTone === 'danger' ? 'border border-cine-danger/25 text-cine-danger' : 'border border-cine-success/25 text-cine-success'"
    ></div>
    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">
            <p class="font-semibold">The requested action could not be completed.</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="mb-5">
        <a href="{{ route('admin.bookings.index', request()->query()) }}" class="mb-4 inline-flex items-center gap-2 text-xs font-semibold text-cine-secondary transition hover:text-cine-primary">
            <x-admin.icon name="arrow-left" class="h-4 w-4" />
            Back to bookings
        </a>
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-bold tracking-tight text-cine-primary sm:text-3xl">{{ $booking->reference }}</h2>
                    <x-admin.badge :status="$paymentStyle">{{ $payment ? str($payment->status->value)->replace('_', ' ')->title() : 'Awaiting payment' }}</x-admin.badge>
                    <x-admin.badge :status="$bookingStyle">{{ str($bookingStatus)->replace('_', ' ')->title() }}</x-admin.badge>
                </div>
                <p class="mt-2 text-xs text-cine-tertiary">Booked {{ $booking->created_at->setTimezone($timezone)->format('D j M Y · H:i') }} · {{ $booking->seats_count }} seat{{ $booking->seats_count === 1 ? '' : 's' }} · <x-admin.money :cents="$booking->total_cents" :currency="$booking->currency" /></p>
            </div>
            @if ($showtimeInFuture)
                <div class="flex flex-wrap gap-2">
                    @if ($canResendEmail)
                        <form method="POST" action="{{ route('admin.bookings.resend-email', $booking) }}" data-action-key="resend" @submit.prevent="submit($event)">
                            @csrf
                            <x-admin.button type="submit" variant="secondary" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs" x-bind:disabled="busy.resend">
                                <span x-show="!busy.resend" class="inline-flex items-center gap-2"><x-admin.icon name="mail" class="h-3.5 w-3.5" />Resend email</span>
                                <span x-cloak x-show="busy.resend" class="inline-flex items-center gap-2"><svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" /></svg>Sending</span>
                            </x-admin.button>
                        </form>
                    @endif
                    @if ($canRefund)
                        @if ($stripeConfigured)
                            <x-admin.button type="button" variant="secondary" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs" x-on:click="$dispatch('open-modal', 'issue-refund')">
                                <x-admin.icon name="undo-2" class="h-3.5 w-3.5" />Issue refund
                            </x-admin.button>
                        @else
                            <span title="Stripe is not configured. Configure Stripe before issuing refunds." class="cursor-not-allowed">
                                <x-admin.button type="button" variant="secondary" disabled class="!min-h-9 !cursor-not-allowed !rounded-lg !px-3 !py-1 !text-xs opacity-50">
                                    <x-admin.icon name="undo-2" class="h-3.5 w-3.5" />Issue refund
                                </x-admin.button>
                            </span>
                        @endif
                    @endif
                    @if ($canCancel)
                        <x-admin.button type="button" variant="danger" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs" x-on:click="$dispatch('open-modal', '{{ $cancellationWindowClosed ? 'cancel-booking-override' : 'cancel-booking' }}')">
                            <x-admin.icon name="ban" class="h-3.5 w-3.5" />Cancel booking
                        </x-admin.button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($booking->isCancelled())
        <div role="status" class="mb-5 rounded-2xl border border-cine-warning/20 bg-cine-warning/10 p-4 text-xs text-cine-secondary">
            <p class="font-semibold text-cine-warning">This booking was cancelled.</p>
            <p class="mt-1">{{ $booking->cancel_reason ?: 'No cancellation reason was recorded.' }}</p>
        </div>
    @endif

    <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1.7fr)_minmax(18rem,0.9fr)]">
        <div class="space-y-4">
            <x-admin.card class="!p-0">
                @php
                    $posterUrl = $movie->posterUrl() ?: $movie->backdropUrl();
                    $backdropUrl = $movie->backdropUrl();
                @endphp
                <div class="relative isolate overflow-hidden rounded-2xl bg-cine-bg">
                    @if ($backdropUrl)
                        <img src="{{ $backdropUrl }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover opacity-20" x-on:error="$el.remove()">
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-r from-cine-surface/95 via-cine-surface/90 to-cine-surface/80"></div>
                    <div class="relative flex min-h-44 items-center gap-4 p-4 sm:gap-5 sm:p-5">
                        <div class="relative h-36 w-28 shrink-0 overflow-hidden rounded-xl border border-cine-accent/30 bg-cine-elevated shadow-cine-card sm:h-40 sm:w-32">
                            <div class="absolute inset-0 flex flex-col items-center justify-center overflow-hidden bg-[radial-gradient(ellipse_at_70%_30%,rgba(56,189,248,.55),transparent_38%),linear-gradient(145deg,#172554,#111827_48%,#3b0764)] px-2 text-center">
                                <svg viewBox="0 0 120 150" class="absolute inset-0 h-full w-full opacity-75" fill="none" aria-hidden="true">
                                    <path d="M-12 105C20 82 43 93 63 64 82 37 89 29 137 15" stroke="url(#comet-{{ $booking->id }})" stroke-width="2" />
                                    <path d="M-8 111C26 89 45 99 68 69" stroke="white" stroke-opacity=".65" stroke-width=".7" />
                                    <circle cx="81" cy="53" r="18" fill="#38bdf8" fill-opacity=".12" />
                                    <circle cx="81" cy="53" r="11" stroke="#bae6fd" stroke-opacity=".55" stroke-width=".7" />
                                    <circle cx="81" cy="53" r="2" fill="white" />
                                    <circle cx="22" cy="33" r="1" fill="white" /><circle cx="103" cy="93" r="1" fill="white" /><circle cx="44" cy="19" r=".8" fill="white" />
                                    <defs><linearGradient id="comet-{{ $booking->id }}" x1="10" y1="110" x2="111" y2="21" gradientUnits="userSpaceOnUse"><stop stop-color="#a78bfa" /><stop offset=".55" stop-color="#38bdf8" /><stop offset="1" stop-color="#f0abfc" /></linearGradient></defs>
                                </svg>
                                <span class="relative mt-auto mb-3 line-clamp-3 text-[10px] font-extrabold uppercase leading-tight tracking-[.12em] text-white drop-shadow">{{ $movie->title }}</span>
                            </div>
                            @if ($posterUrl)
                                <img src="{{ $posterUrl }}" alt="{{ $movie->title }} poster" loading="lazy" class="absolute inset-0 h-full w-full object-cover" x-on:error="$el.remove()">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="mb-2 flex items-center gap-2 text-xs font-semibold text-cine-primary">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-cine-accent/15 text-cine-accent"><x-admin.icon name="calendar-clock" class="h-3.5 w-3.5" /></span>
                                <span>Showtime</span>
                            </div>
                            <h3 class="text-xl font-bold tracking-tight text-cine-primary sm:text-2xl">{{ $movie->title }}</h3>
                            <div class="mt-5 flex flex-wrap gap-2 text-[11px] font-medium text-cine-secondary">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-cine-border/15 bg-cine-surface/80 px-3 py-2"><x-admin.icon name="building" class="h-3.5 w-3.5 text-cine-accent" />{{ $cinema->name }}</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-cine-border/15 bg-cine-surface/80 px-3 py-2"><x-admin.icon name="monitor" class="h-3.5 w-3.5 text-cine-accent" />{{ $screen->name }}</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-cine-border/15 bg-cine-surface/80 px-3 py-2"><x-admin.icon name="calendar" class="h-3.5 w-3.5 text-cine-accent" />{{ $startsAt->format('D, j M Y') }}</span>
                            </div>
                        </div>
                        <div class="hidden shrink-0 border-l border-cine-border/15 pl-5 text-right sm:block">
                            <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-cine-tertiary">Show time</p>
                            <p class="mt-1 text-xl font-bold tracking-tight text-cine-primary">{{ $startsAt->format('g:i A') }}</p>
                            <p class="mt-0.5 text-[11px] text-cine-secondary">to {{ $endsAt->format('g:i A') }}</p>
                        </div>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card class="!p-0">
                <div class="flex items-center justify-between border-b border-cine-border/10 px-5 py-4">
                    <h3 class="font-semibold text-cine-primary">Seats &amp; tickets</h3>
                    <span class="rounded-full border border-cine-border/15 bg-cine-bg px-2.5 py-1 text-[10px] font-semibold text-cine-secondary">{{ $booking->items->count() }} ticket{{ $booking->items->count() === 1 ? '' : 's' }}</span>
                </div>
                @if ($booking->items->isEmpty())
                    <p class="p-5 text-xs text-cine-tertiary">No seats are associated with this booking.</p>
                @else
                    <div class="divide-y divide-cine-border/10 px-5">
                        @foreach ($booking->items as $item)
                            @php
                                $ticket = $item->ticket;
                                $ticketStyle = match ($ticket?->status?->value) {
                                    'valid' => 'success',
                                    'used' => 'info',
                                    'void', 'voided' => 'danger',
                                    default => 'warning',
                                };
                            @endphp
                            <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-cine-border/15 bg-cine-bg text-xs font-bold text-cine-primary">{{ $item->seat_label }}</span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-cine-primary">Seat {{ $item->seat_label }}</p>
                                        <p class="mt-0.5 truncate text-[10px] text-cine-tertiary">{{ $ticket?->ticket_number ?: 'Ticket not issued' }}</p>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-cine-primary"><x-admin.money :cents="$item->price_cents" :currency="$booking->currency" /></span>
                                @if ($ticket)
                                    <x-admin.badge :status="$ticketStyle">{{ $ticket->isVoid() ? 'Void' : str($ticket->status->value)->title() }}</x-admin.badge>
                                    @if ($ticket->status->value === 'valid' && $showtimeInFuture)
                                        <button
                                            type="button"
                                            aria-label="Void ticket {{ $ticket->ticket_number }}"
                                            title="Void ticket"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-cine-danger/15 bg-cine-danger/5 text-cine-danger transition hover:bg-cine-danger/15 focus:outline-none focus:ring-2 focus:ring-cine-danger/40"
                                            x-on:click="$dispatch('set-void-ticket', @js([
                                                'url' => route('admin.bookings.tickets.void', [$booking, $ticket]),
                                                'number' => $ticket->ticket_number,
                                                'seat' => $item->seat_label,
                                                'showtime' => $startsAt->format('D j M · H:i'),
                                            ])); $dispatch('open-modal', 'void-ticket')"
                                        >
                                            <x-admin.icon name="ticket-x" class="h-4 w-4" />
                                        </button>
                                    @endif
                                @else
                                    <x-admin.badge status="warning">No ticket</x-admin.badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card>
                <h3 class="font-semibold text-cine-primary">Activity</h3>
                @if ($timeline->isEmpty())
                    <p class="mt-4 text-xs text-cine-tertiary">No booking activity recorded.</p>
                @else
                    <ol class="mt-5 space-y-0">
                        @foreach ($timeline as $event)
                            @php
                                $eventColor = match ($event['tone']) {
                                    'success' => 'bg-cine-success',
                                    'warning' => 'bg-cine-warning',
                                    'danger' => 'bg-cine-danger',
                                    default => 'bg-cine-accent',
                                };
                            @endphp
                            <li class="relative flex gap-3 pb-5 last:pb-0">
                                @if (! $loop->last)
                                    <span aria-hidden="true" class="absolute bottom-0 left-[5px] top-3 w-px bg-cine-border/20"></span>
                                @endif
                                <span class="relative mt-1 h-3 w-3 shrink-0 rounded-full {{ $eventColor }} ring-4 ring-cine-surface"></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-cine-primary">{{ $event['label'] }}</p>
                                    <p class="mt-1 text-[10px] leading-5 text-cine-secondary">{{ $event['detail'] ?: '—' }}</p>
                                    <p class="text-[9px] text-cine-tertiary">{{ $event['at']->setTimezone($timezone)->format('D j M · H:i') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-admin.card>
        </div>

        <aside class="space-y-4">
            <x-admin.card>
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-cine-primary">Customer</h3>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-cine-accent/15 text-xs font-bold text-cine-accent">{{ str($booking->user->name)->initials() }}</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-cine-primary">{{ $booking->user->name }}</p>
                <p class="mt-1 break-all text-xs text-cine-secondary">{{ $booking->user->email }}</p>
                <div class="mt-4 space-y-2 border-t border-cine-border/10 pt-4 text-xs">
                    <div class="flex justify-between gap-3"><span class="text-cine-tertiary">Customer since</span><span class="text-right text-cine-secondary">{{ $booking->user->created_at->setTimezone($timezone)->format('M Y') }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-cine-tertiary">Bookings</span><span class="text-right font-semibold text-cine-primary">{{ $booking->user->bookings()->count() }} total</span></div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-cine-primary">Payment</h3>
                    <x-admin.badge :status="$paymentStyle">{{ $payment ? str($payment->status->value)->replace('_', ' ')->title() : 'Awaiting' }}</x-admin.badge>
                </div>
                <p class="mt-4 text-2xl font-bold tracking-tight text-cine-primary"><x-admin.money :cents="$payment?->amount_cents ?? $booking->total_cents" :currency="$payment?->currency ?? $booking->currency" /></p>
                @if ($payment)
                    <div class="mt-4 space-y-3 border-t border-cine-border/10 pt-4 text-xs">
                        <div class="flex justify-between gap-3"><span class="text-cine-tertiary">Method</span><span class="text-right text-cine-secondary">{{ str($payment->gateway)->title() }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-cine-tertiary">Payment ID</span><span class="max-w-40 truncate text-right font-mono text-cine-secondary">{{ $payment->gateway_payment_intent_id ?: $payment->gateway_session_id ?: '—' }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-cine-tertiary">Paid at</span><span class="text-right text-cine-secondary">{{ $payment->paid_at?->setTimezone($timezone)->format('D j M · H:i') ?: '—' }}</span></div>
                        @if ($payment->failure_reason)
                            <p class="rounded-lg bg-cine-danger/10 p-2 text-[10px] text-cine-danger">{{ $payment->failure_reason }}</p>
                        @endif
                    </div>
                @else
                    <p class="mt-2 text-[10px] text-cine-tertiary">No payment attempt has been recorded.</p>
                @endif
            </x-admin.card>

            <x-admin.card>
                <h3 class="font-semibold text-cine-primary">Order summary</h3>
                <div class="mt-4 space-y-3 border-b border-cine-border/10 pb-4 text-xs">
                    <div class="flex justify-between gap-3"><span class="text-cine-secondary">{{ $booking->items->count() }} seat{{ $booking->items->count() === 1 ? '' : 's' }}</span><span class="text-cine-primary"><x-admin.money :cents="$booking->items->sum('price_cents')" :currency="$booking->currency" /></span></div>
                    <div class="flex justify-between gap-3"><span class="text-cine-secondary">Other charges</span><span class="text-cine-primary"><x-admin.money :cents="max(0, $booking->total_cents - $booking->items->sum('price_cents'))" :currency="$booking->currency" /></span></div>
                </div>
                <div class="mt-4 flex justify-between gap-3 text-sm font-bold"><span class="text-cine-primary">Total</span><span class="text-cine-primary"><x-admin.money :cents="$booking->total_cents" :currency="$booking->currency" /></span></div>
            </x-admin.card>

            <x-admin.card>
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-cine-primary">Refunds</h3>
                    <span class="text-[10px] text-cine-tertiary">{{ $booking->refunds->count() }}</span>
                </div>
                @unless ($stripeConfigured)
                    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-cine-warning/25 bg-cine-warning/10 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10px] leading-5 text-cine-warning">Stripe is not configured. Refunds cannot be issued right now.</p>
                        <x-admin.button :href="route('admin.settings.edit')" variant="secondary" class="!min-h-8 !shrink-0 !rounded-lg !px-3 !py-1 !text-[10px]">Open Settings</x-admin.button>
                    </div>
                @endunless
                @forelse ($booking->refunds as $refund)
                    <div class="mt-4 rounded-xl border border-cine-border/10 bg-cine-bg/50 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-bold text-cine-primary"><x-admin.money :cents="$refund->amount_cents" :currency="$refund->currency" /></span>
                            <x-admin.badge :status="$refund->isSucceeded() ? 'success' : ($refund->isFailed() ? 'danger' : 'warning')">{{ str($refund->status->value)->title() }}</x-admin.badge>
                        </div>
                        <p class="mt-2 text-[10px] text-cine-secondary">{{ str($refund->reason)->replace('_', ' ')->title() }}</p>
                        <p class="mt-1 text-[9px] text-cine-tertiary">{{ ($refund->processed_at ?? $refund->created_at)->setTimezone($timezone)->format('D j M · H:i') }}</p>
                        @if ($refund->failure_message)
                            <p class="mt-2 text-[10px] text-cine-danger">{{ $refund->failure_message }}</p>
                        @endif
                    </div>
                @empty
                    <p class="mt-3 text-xs text-cine-tertiary">No refunds recorded.</p>
                @endforelse
            </x-admin.card>
        </aside>
    </div>
    @if ($canCancel)
        <x-modal name="cancel-booking" title="Cancel booking" maxWidth="lg" focusable panel-class="!rounded-2xl">
            <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}">
                @csrf
                <input type="hidden" name="confirm_cancellation" value="1">
                <input type="hidden" name="also_refund" value="0" x-bind:disabled="alsoRefund">
                <div class="flex items-start gap-3 border-b border-cine-border/10 px-5 py-4 sm:px-6">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cine-danger/10 text-cine-danger"><x-admin.icon name="ticket-x" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold text-cine-primary">Cancel booking {{ $booking->reference }}?</h2>
                        <p class="mt-1 text-[10px] text-cine-tertiary">{{ $movie->title }} · {{ $startsAt->format('D j M · H:i') }} · {{ $screen->name }} · Seats {{ $booking->items->pluck('seat_label')->implode(', ') }}</p>
                    </div>
                    <button type="button" aria-label="Close dialog" class="rounded-md p-1 text-cine-tertiary hover:bg-cine-elevated hover:text-cine-primary" x-on:click="$dispatch('close-modal', 'cancel-booking')"><span aria-hidden="true">×</span></button>
                </div>
                <div class="space-y-4 px-5 py-4 sm:px-6">
                    <div class="space-y-1.5 rounded-xl border border-cine-accent/10 bg-cine-bg/60 p-3 text-[10px] leading-4 text-cine-secondary">
                        <p class="flex gap-2"><span class="font-bold text-cine-success">✓</span><span>Seats {{ $booking->items->pluck('seat_label')->implode(', ') }} are released for other customers.</span></p>
                        <p class="flex gap-2"><span class="font-bold text-cine-success">✓</span><span>All valid tickets are voided and stop working at the door.</span></p>
                        <p class="flex gap-2"><span class="font-bold text-cine-success">✓</span><span>The customer gets a cancellation email at {{ $booking->user->email }}.</span></p>
                        <p class="flex gap-2"><span class="font-bold text-cine-success">✓</span><span>Saved in the audit log with your name.</span></p>
                    </div>

                    <div>
                        <x-input-label for="cancel-reason" value="Reason" class="!text-[10px] !font-semibold !text-cine-secondary" />
                        <select id="cancel-reason" name="cancel_reason" required class="mt-1.5 min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-[10px] text-cine-primary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                            <option value="">Select a reason</option>
                            <option value="Customer request">Customer request</option>
                            <option value="Show problem / technical issue">Show problem / technical issue</option>
                            <option value="Duplicate booking">Duplicate booking</option>
                            <option value="Other">Other</option>
                        </select>
                        <p x-cloak x-show="errorMessages('cancel', 'cancel_reason').length" x-text="errorMessages('cancel', 'cancel_reason')[0]" role="alert" class="mt-1 text-[10px] text-cine-danger"></p>
                    </div>
                    <div>
                        <x-input-label for="cancel-note" value="Note (optional)" class="!text-[10px] !font-semibold !text-cine-secondary" />
                        <textarea id="cancel-note" name="note" rows="2" maxlength="255" placeholder="Add a short note for the team…" class="mt-1.5 w-full resize-y rounded-lg border border-cine-border/15 bg-cine-bg px-3 py-2 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20"></textarea>
                    </div>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-cine-border/10 bg-cine-bg/50 p-3" :class="alsoRefund ? 'border-cine-accent/40' : ''">
                        <span>
                            <span class="block text-[10px] font-semibold text-cine-primary">Also refund ${{ number_format($refundableCents / 100, 2) }} (100%)</span>
                            <span class="mt-0.5 block text-[9px] text-cine-tertiary">Within the cancellation window the customer is entitled to a full refund.</span>
                            @unless ($stripeConfigured)
                                <span class="mt-1 block text-[9px] text-cine-warning">Stripe is not configured, refund later</span>
                            @endunless
                        </span>
                        <input type="checkbox" name="also_refund" value="1" x-model="alsoRefund" @disabled(! $stripeConfigured || $refundableCents <= 0) class="h-4 w-4 rounded border-cine-border/25 bg-cine-bg text-cine-accent focus:ring-cine-accent">
                    </label>
                    @if ($stripeConfigured && $refundableCents > 0)
                        <input type="hidden" name="amount" value="{{ number_format($refundableCents / 100, 2, '.', '') }}">
                    @endif
                    <p x-cloak x-show="errorMessages('cancel', 'amount').length" x-text="errorMessages('cancel', 'amount')[0]" role="alert" class="text-[10px] text-cine-danger"></p>
                    <p x-cloak x-show="errorMessages('cancel').length" x-text="errorMessages('cancel')[0]" role="alert" class="text-[10px] text-cine-danger"></p>
                </div>
                <div class="flex justify-end gap-2 border-t border-cine-border/10 px-5 py-3 sm:px-6">
                    <x-admin.button type="button" variant="secondary" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-[10px]" x-on:click="$dispatch('close-modal', 'cancel-booking')">Keep booking</x-admin.button>
                    <x-admin.button type="submit" variant="danger" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-[10px]">
                        <span x-show="!busy.cancel">Cancel booking</span>
                        <span x-cloak x-show="busy.cancel" class="inline-flex items-center gap-2"><svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" /></svg>Submitting</span>
                    </x-admin.button>
                </div>
            </form>
        </x-modal>

        <x-modal name="cancel-booking-override" title="Cancel booking with admin override" maxWidth="lg" focusable panel-class="!rounded-2xl">
            <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}">
                @csrf
                <input type="hidden" name="confirm_cancellation" value="1">
                <div class="flex items-start gap-3 border-b border-cine-border/10 px-5 py-4 sm:px-6">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cine-danger/10 text-cine-danger"><x-admin.icon name="ticket-x" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold text-cine-primary">Cancel booking {{ $booking->reference }}?</h2>
                        <p class="mt-1 text-[10px] text-cine-tertiary">{{ $movie->title }} · {{ $startsAt->format('D j M · H:i') }} · {{ $screen->name }} · Seats {{ $booking->items->pluck('seat_label')->implode(', ') }}</p>
                    </div>
                    <button type="button" aria-label="Close dialog" class="rounded-md p-1 text-cine-tertiary hover:bg-cine-elevated hover:text-cine-primary" x-on:click="$dispatch('close-modal', 'cancel-booking-override')"><span aria-hidden="true">×</span></button>
                </div>
                <div class="space-y-4 px-5 py-4 sm:px-6">
                    <div class="flex gap-2 rounded-xl border border-cine-warning/25 bg-cine-warning/10 p-3 text-[10px] leading-4 text-cine-warning">
                        <x-admin.icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5" />
                        <p>The {{ $cancelWindowHours }}-hour cancellation window has passed. You can still cancel as an admin, but a reason is required and no refund is issued automatically.</p>
                    </div>
                    <div>
                        <x-input-label for="cancel-reason-override" value="Reason" class="!text-[10px] !font-semibold !text-cine-secondary" />
                        <select id="cancel-reason-override" name="cancel_reason" required class="mt-1.5 min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-[10px] text-cine-primary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                            <option value="">Select a reason</option>
                            <option value="Customer request">Customer request</option>
                            <option value="Show problem / technical issue">Show problem / technical issue</option>
                            <option value="Duplicate booking">Duplicate booking</option>
                            <option value="Other">Other</option>
                        </select>
                        <p x-cloak x-show="errorMessages('override', 'cancel_reason').length" x-text="errorMessages('override', 'cancel_reason')[0]" role="alert" class="mt-1 text-[10px] text-cine-danger"></p>
                    </div>
                    <div>
                        <x-input-label for="admin-override-reason" value="Admin override reason *" class="!text-[10px] !font-semibold !text-cine-secondary" />
                        <textarea id="admin-override-reason" name="override_reason" rows="3" maxlength="255" required placeholder="Explain why this booking is cancelled after the cutoff…" class="mt-1.5 w-full resize-y rounded-lg border border-cine-border/15 bg-cine-bg px-3 py-2 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20"></textarea>
                        <p x-cloak x-show="errorMessages('override', 'override_reason').length" x-text="errorMessages('override', 'override_reason')[0]" role="alert" class="mt-1 text-[10px] text-cine-danger"></p>
                        <p class="mt-1 text-[9px] text-cine-danger">This is stored in the audit log.</p>
                    </div>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-cine-border/10 bg-cine-bg/50 p-3">
                        <span>
                            <span class="block text-[10px] font-semibold text-cine-primary">Also refund ${{ number_format($refundableCents / 100, 2) }}</span>
                            <span class="mt-0.5 block text-[9px] text-cine-tertiary">Only by default after the cutoff. You can refund later from this page.</span>
                            @unless ($stripeConfigured)
                                <span class="mt-1 block text-[9px] text-cine-warning">Stripe is not configured, refund later</span>
                            @endunless
                        </span>
                        <input type="checkbox" name="also_refund" value="1" x-model="alsoRefund" @disabled(! $stripeConfigured || $refundableCents <= 0) class="h-4 w-4 rounded border-cine-border/25 bg-cine-bg text-cine-accent focus:ring-cine-accent">
                    </label>
                    @if ($stripeConfigured && $refundableCents > 0)
                        <input type="hidden" name="amount" value="{{ number_format($refundableCents / 100, 2, '.', '') }}">
                    @endif
                    <p x-cloak x-show="errorMessages('override', 'amount').length" x-text="errorMessages('override', 'amount')[0]" role="alert" class="text-[10px] text-cine-danger"></p>
                    <p x-cloak x-show="errorMessages('override').length" x-text="errorMessages('override')[0]" role="alert" class="text-[10px] text-cine-danger"></p>
                </div>
                <div class="flex justify-end gap-2 border-t border-cine-border/10 px-5 py-3 sm:px-6">
                    <x-admin.button type="button" variant="secondary" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-[10px]" x-on:click="$dispatch('close-modal', 'cancel-booking-override')">Keep booking</x-admin.button>
                    <x-admin.button type="submit" variant="danger" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-[10px]">
                        <span x-show="!busy.override">Cancel with override</span>
                        <span x-cloak x-show="busy.override" class="inline-flex items-center gap-2"><svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" /></svg>Submitting</span>
                    </x-admin.button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($canRefund && $stripeConfigured)
        <x-modal name="issue-refund" title="Issue refund" maxWidth="lg" focusable panel-class="!rounded-2xl">
            <form method="POST" action="{{ route('admin.bookings.refund', $booking) }}" data-action-key="refund" data-modal="issue-refund" @submit.prevent="submit($event)">
                @csrf
                <input type="hidden" name="confirm_refund" value="1">
                <div class="flex items-start gap-3 border-b border-cine-border/10 px-5 py-4 sm:px-6">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cine-accent/15 text-cine-accent"><x-admin.icon name="undo-2" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold text-cine-primary">Issue refund</h2>
                        <p class="mt-1 text-[10px] text-cine-tertiary">Booking {{ $booking->reference }} · Paid <x-admin.money :cents="$refundablePayment?->amount_cents ?? 0" :currency="$refundablePayment?->currency ?? $booking->currency" /> · Already refunded <x-admin.money :cents="max(0, ($refundablePayment?->amount_cents ?? 0) - $refundableCents)" :currency="$refundablePayment?->currency ?? $booking->currency" /></p>
                    </div>
                    <button type="button" aria-label="Close dialog" class="rounded-md p-1 text-cine-tertiary hover:bg-cine-elevated hover:text-cine-primary" x-on:click="$dispatch('close-modal', 'issue-refund')"><span aria-hidden="true">×</span></button>
                </div>
                <div class="space-y-4 px-5 py-4 sm:px-6">
                    <fieldset class="space-y-1.5">
                        <legend class="sr-only">Refund type</legend>
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-cine-accent/40 bg-cine-accent/10 px-3 py-2 text-[10px] font-semibold text-cine-primary">
                            <input type="radio" name="refund_type" value="full" checked x-on:change="setRefundType('full')" class="h-3.5 w-3.5 border-cine-border/30 text-cine-accent focus:ring-cine-accent">
                            Full refund · ${{ number_format($refundableCents / 100, 2) }}
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-cine-border/15 bg-cine-bg/40 px-3 py-2 text-[10px] font-semibold text-cine-secondary">
                            <input type="radio" name="refund_type" value="partial" x-on:change="setRefundType('partial')" class="h-3.5 w-3.5 border-cine-border/30 text-cine-accent focus:ring-cine-accent">
                            Partial refund
                        </label>
                    </fieldset>
                    <div>
                        <x-input-label for="refund-amount" value="Amount" class="!text-[10px] !font-semibold !text-cine-secondary" />
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-cine-secondary">$</span>
                            <x-text-input id="refund-amount" name="amount" type="number" min="0.01" max="{{ number_format($refundableCents / 100, 2, '.', '') }}" step="0.01" x-model="refundAmount" x-bind:readonly="refundType === 'full'" required class="!min-h-10 !w-full !rounded-lg !border-cine-border/15 !bg-cine-bg !pl-7 !pr-20 !text-[10px] !text-cine-primary focus:!border-cine-accent/50 focus:!ring-cine-accent/20" />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-cine-tertiary">max ${{ number_format($refundableCents / 100, 2) }}</span>
                        </div>
                        <p x-cloak x-show="errorMessages('refund', 'amount').length" x-text="errorMessages('refund', 'amount')[0]" role="alert" class="mt-1 text-[10px] text-cine-danger"></p>
                        <p class="mt-1 text-[9px] text-cine-tertiary">Cannot be more than the remaining refundable balance.</p>
                    </div>
                    <div>
                        <x-input-label for="refund-reason" value="Reason" class="!text-[10px] !font-semibold !text-cine-secondary" />
                        <select id="refund-reason" name="reason" required class="mt-1.5 min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-[10px] text-cine-primary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                            <option value="">Select a reason</option>
                            <option value="requested_by_customer">Customer request</option>
                            <option value="duplicate">Duplicate payment</option>
                            <option value="fraudulent">Fraudulent</option>
                        </select>
                        <p x-cloak x-show="errorMessages('refund', 'reason').length" x-text="errorMessages('refund', 'reason')[0]" role="alert" class="mt-1 text-[10px] text-cine-danger"></p>
                    </div>
                    <div class="flex gap-2 rounded-lg border border-cine-border/10 bg-cine-bg/60 p-3 text-[10px] leading-4 text-cine-secondary">
                        <span class="font-semibold text-cine-warning">Pending</span>
                        <p>The refund is sent to Stripe and shows as pending until Stripe confirms it. You’ll see it in the Refunds card and the Refunds page.</p>
                    </div>
                    <p x-cloak x-show="errorMessages('refund').length" x-text="errorMessages('refund')[0]" role="alert" class="text-[10px] text-cine-danger"></p>
                </div>
                <div class="flex justify-end gap-2 border-t border-cine-border/10 px-5 py-3 sm:px-6">
                    <x-admin.button type="button" variant="secondary" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-[10px]" x-on:click="$dispatch('close-modal', 'issue-refund')">Cancel</x-admin.button>
                    <x-admin.button type="submit" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-[10px]" x-bind:disabled="busy.refund">
                        <span x-show="!busy.refund" x-text="`Refund $${Number(refundAmount || 0).toFixed(2)}`">Refund ${{ number_format($refundableCents / 100, 2) }}</span>
                        <span x-cloak x-show="busy.refund" class="inline-flex items-center gap-2"><svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" /></svg>Submitting</span>
                    </x-admin.button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($booking->items->contains(fn ($item) => $item->ticket?->status?->value === 'valid') && $showtimeInFuture)
        <x-modal name="void-ticket" title="Void ticket" maxWidth="md" focusable panel-class="!rounded-2xl">
            <form method="POST" :action="ticket.url" data-action-key="void" data-modal="void-ticket" @submit.prevent="submit($event)">
                @csrf
                <div class="flex items-start gap-3 border-b border-cine-border/10 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cine-danger/10 text-cine-danger"><x-admin.icon name="ticket-x" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold text-cine-primary">Void ticket <span x-text="ticket.number"></span>?</h2>
                        <p class="mt-1 text-[10px] text-cine-tertiary">Seat <span x-text="ticket.seat"></span> · {{ $cinema->name }} · <span x-text="ticket.showtime"></span></p>
                    </div>
                    <button type="button" aria-label="Close dialog" class="rounded-md p-1 text-cine-tertiary hover:bg-cine-elevated hover:text-cine-primary" x-on:click="$dispatch('close-modal', 'void-ticket')"><span aria-hidden="true">×</span></button>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <div class="flex gap-2 rounded-xl border border-cine-warning/25 bg-cine-warning/10 p-3 text-[10px] leading-4 text-cine-warning">
                        <x-admin.icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5" />
                        <p>The QR code stops working at the door immediately. Voiding does not refund the seat; issue a refund separately if needed.</p>
                    </div>
                    <div>
                        <x-input-label for="void-ticket-reason" value="Reason *" class="!text-[10px] !font-semibold !text-cine-secondary" />
                        <textarea id="void-ticket-reason" name="reason" rows="3" maxlength="255" required placeholder="Why is this ticket being voided?" class="mt-1.5 w-full resize-y rounded-lg border border-cine-border/15 bg-cine-bg px-3 py-2 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20"></textarea>
                        <p x-cloak x-show="errorMessages('void', 'reason').length" x-text="errorMessages('void', 'reason')[0]" role="alert" class="mt-1 text-[10px] text-cine-danger"></p>
                    </div>
                    <p x-cloak x-show="errorMessages('void').length" x-text="errorMessages('void')[0]" role="alert" class="text-[10px] text-cine-danger"></p>
                </div>
                <div class="flex justify-end gap-2 border-t border-cine-border/10 px-5 py-3">
                    <x-admin.button type="button" variant="secondary" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-[10px]" x-on:click="$dispatch('close-modal', 'void-ticket')">Keep ticket</x-admin.button>
                    <x-admin.button type="submit" variant="danger" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-[10px]" x-bind:disabled="busy.void">
                        <span x-show="!busy.void">Void ticket</span>
                        <span x-cloak x-show="busy.void" class="inline-flex items-center gap-2"><svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" /></svg>Submitting</span>
                    </x-admin.button>
                </div>
            </form>
        </x-modal>
    @endif
    </div>
</x-admin.layout>
