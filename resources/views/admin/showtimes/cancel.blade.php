<x-admin.layout title="Cancel showtime" breadcrumb="CineBook / Admin / Showtime">
    <x-admin.page-header
        title="Cancel showtime"
        subtitle="Review affected bookings and seats before confirming this change."
        :back-url="route('admin.showtimes.index')"
        back-label="Back to schedule"
    />

    <div class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-cine-danger">
        <p class="text-sm font-semibold">Booked show · cancellation, not deletion</p>
        <p class="mt-1 text-xs leading-5">The showtime record will remain in the database. Active bookings will be cancelled, tickets voided and held seats released. This action cannot be undone.</p>
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(260px,320px)]">
        <x-admin.card class="!p-0">
            <div class="border-b border-cine-border/10 px-5 py-4 sm:px-6">
                <h2 class="text-base font-bold text-cine-primary">{{ $showtime->movie->title }} · {{ $showtime->screen->name }}</h2>
                <p class="mt-1 text-xs text-cine-secondary">{{ $showtime->screen->cinema->name }} · {{ $showtime->starts_at->format('l, M j, Y · H:i') }}–{{ $showtime->ends_at->format('H:i') }}</p>
            </div>

            <div class="grid gap-3 p-4 sm:grid-cols-3 sm:p-5">
                @foreach ([
                    ['label' => 'Confirmed bookings', 'value' => $impact['confirmed']],
                    ['label' => 'Tickets to void', 'value' => $impact['tickets']],
                    ['label' => 'Paid booking total', 'cents' => $impact['amount_cents']],
                ] as $stat)
                    <div class="rounded-xl border border-cine-border/10 bg-cine-bg/50 p-4">
                        <p class="text-xs text-cine-tertiary">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-xl font-bold text-cine-primary">@if (isset($stat['cents']))<x-admin.money :cents="$stat['cents']" />@else{{ $stat['value'] }}@endif</p>
                    </div>
                @endforeach
            </div>

            <div class="px-4 pb-4 sm:px-5 sm:pb-5">
                <p class="rounded-xl border border-cine-warning/20 bg-cine-warning/10 p-3 text-xs leading-5 text-cine-warning">
                    {{ number_format($impact['pending_payment']) }} pending bookings will be cancelled and their pending payment attempts expired. {{ number_format($impact['active_holds']) }} active seat holds will be released. Successful payment records remain unchanged; no refund is created or processed here.
                </p>

                <form method="POST" action="{{ route('admin.showtimes.cancel', $showtime) }}" class="mt-4 space-y-4" novalidate>
                    @csrf
                    <div>
                        <label for="cancel_reason" class="mb-2 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Cancellation reason <span class="text-cine-danger">*</span></label>
                        <textarea
                            id="cancel_reason"
                            name="cancel_reason"
                            required
                            maxlength="255"
                            rows="3"
                            placeholder="Explain why this showtime is being cancelled."
                            class="w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20"
                            @error('cancel_reason') aria-invalid="true" aria-describedby="cancel-reason-error" @enderror
                        >{{ old('cancel_reason') }}</textarea>
                        <x-admin.field-error field="cancel_reason" id="cancel-reason-error" />
                    </div>

                    <div>
                        <label class="flex items-start gap-2 text-xs leading-5 text-cine-secondary">
                            <input
                                type="checkbox"
                                name="confirm_cancellation"
                                value="1"
                                required
                                @checked(old('confirm_cancellation'))
                                class="mt-0.5 rounded border-cine-border/20 bg-cine-bg text-cine-danger focus:ring-cine-danger"
                                @error('confirm_cancellation') aria-invalid="true" aria-describedby="confirm-cancellation-error" @enderror
                            >
                            <span>I understand that {{ number_format($impact['confirmed']) }} confirmed bookings will be cancelled and their tickets voided. Payment refunds are not part of this action.</span>
                        </label>
                        <x-admin.field-error field="confirm_cancellation" id="confirm-cancellation-error" />
                    </div>

                    <div class="flex flex-wrap gap-2 border-t border-cine-border/10 pt-4">
                        <x-admin.button type="submit" variant="danger">Cancel showtime</x-admin.button>
                        <x-admin.button :href="route('admin.showtimes.index')" variant="secondary">Keep showtime</x-admin.button>
                    </div>
                </form>
            </div>
        </x-admin.card>

        <aside class="space-y-3">
            <x-admin.card title="Impact summary">
                <dl class="space-y-4 text-xs">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-cine-secondary">Confirmed bookings</dt>
                        <dd class="font-semibold text-cine-primary">{{ number_format($impact['confirmed']) }} · cancelled</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-cine-secondary">Pending bookings</dt>
                        <dd class="font-semibold text-cine-primary">{{ number_format($impact['pending_payment']) }} · cancelled</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-cine-secondary">Tickets</dt>
                        <dd class="font-semibold text-cine-primary">{{ number_format($impact['tickets']) }} · voided</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-cine-secondary">Active seat holds</dt>
                        <dd class="font-semibold text-cine-primary">{{ number_format($impact['active_holds']) }} · released</dd>
                    </div>
                </dl>
                <p class="mt-5 border-t border-cine-border/10 pt-4 text-xs leading-5 text-cine-tertiary">
                    The showtime, cancellation reason and admin account will be saved to the database. No refund records will be added or changed.
                </p>
            </x-admin.card>
        </aside>
    </div>
</x-admin.layout>
