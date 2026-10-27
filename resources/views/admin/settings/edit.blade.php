<x-admin.layout title="Reservation settings" breadcrumb="Operations">
    <div class="mb-5 rounded-md bg-[#183663] px-3 py-3 text-xs leading-5 text-cine-primary">
        ⓘ Changes apply immediately to new holds and cancellation decisions<br>
        Existing holds keep their expiry and booked prices don't change. Cancellation-window updates also apply to existing bookings. Every change is audited.
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-md border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(280px,.65fr)]">
        @csrf
        @method('PUT')
        <x-admin.card>
            <h2 class="mb-5 text-lg font-bold text-cine-primary">Four configurable values</h2>
            <div class="space-y-4">
                @foreach ([
                    ['key' => 'hold_minutes', 'label' => 'Hold duration · minutes', 'help' => 'Allowed range: 2–30 minutes. All selected seats are held together.'],
                    ['key' => 'max_seats_per_booking', 'label' => 'Maximum seats per booking', 'help' => 'Allowed range: 1–10 seats. Customers must select at least one seat.'],
                    ['key' => 'cancel_window_hours', 'label' => 'Customer cancellation window · hours before show', 'help' => 'Allowed range: 0–72 hours. Only confirmed, unscanned bookings are eligible.'],
                    ['key' => 'premium_surcharge_cents', 'label' => 'Premium seat surcharge · USD', 'help' => 'Allowed range: $0.00–$50.00. Added to the showtime base price.'],
                ] as $field)
                    <label class="block rounded-lg border border-cine-border/10 bg-cine-bg px-3 py-3">
                        <span class="block text-[10px] uppercase tracking-wide text-cine-secondary">{{ $field['label'] }}</span>
                        <input name="{{ $field['key'] }}" value="{{ old($field['key'], $settings[$field['key']]) }}" @class(['mt-1 block w-full border-0 bg-transparent p-0 text-sm text-cine-primary focus:ring-0', 'text-cine-danger' => $errors->has($field['key'])]) @if ($field['key'] === 'premium_surcharge_cents') inputmode="decimal" step="0.01" @endif>
                        <span class="mt-1 block text-[11px] text-cine-tertiary">{{ $field['help'] }}</span>
                    </label>
                @endforeach
            </div>
            <div class="mt-4 flex gap-3">
                <x-admin.button type="submit">Save settings</x-admin.button>
                <x-admin.button :href="route('admin.settings.edit')" variant="secondary">Discard changes</x-admin.button>
            </div>
        </x-admin.card>
        <div class="space-y-4">
            <x-admin.card title="Fixed operating rules">
                <p class="text-sm text-cine-secondary">Sales close <strong class="text-cine-primary">15 minutes</strong> before start</p>
                <p class="mt-4 text-sm text-cine-secondary">Entry opens <strong class="text-cine-primary">60 minutes</strong> before start</p>
                <p class="mt-4 text-sm text-cine-secondary">Entry closes <strong class="text-cine-primary">30 minutes</strong> after start</p>
                <p class="mt-4 text-xs leading-5 text-cine-tertiary">Informational only. These rules aren't configurable here.</p>
            </x-admin.card>
            <x-admin.card title="Payment environment">
                <p class="text-sm leading-6 text-cine-secondary">Stripe TEST MODE hosted Checkout. No real-money processing and no on-site card-entry form.</p>
            </x-admin.card>
        </div>
    </form>
</x-admin.layout>
