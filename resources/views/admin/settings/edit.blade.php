<x-admin.layout title="Settings" breadcrumb="System">
    <x-admin.page-header title="Settings" subtitle="Manage persisted cinema settings and review connected services." />
    <x-admin.toast />
    @if($errors->any())<div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ $errors->first() }}</div>@endif
    <nav class="mb-5 flex flex-wrap gap-2" aria-label="Settings sections">
        @foreach(['general' => 'General', 'rules' => 'Booking rules', 'payments' => 'Payments', 'emails' => 'Emails'] as $key => $label)<a href="{{ route('admin.settings.edit', ['tab' => $key]) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'border-cine-accent/50 bg-cine-accent/10 text-cine-accent' : 'border-cine-border/10 bg-cine-surface text-cine-secondary' }}">{{ $label }}</a>@endforeach
    </nav>
    @if($tab === 'general')
        <x-admin.card>
            <h2 class="text-lg font-semibold text-cine-primary">General</h2>
            <p class="mt-1 text-sm text-cine-secondary">Changes are stored in the settings table and audited.</p>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-5 grid gap-4 md:grid-cols-2" x-data="{saving:false}" x-on:submit="if(saving){$event.preventDefault();return} saving=true">@csrf @method('PATCH')
                <x-admin.input name="values[cinema_name]" label="Cinema name" :value="$values['cinema_name']" required maxlength="120" />
                <x-admin.input name="values[support_email]" label="Support email" type="email" :value="$values['support_email']" required />
                <x-admin.select name="values[timezone]" label="Cinema timezone" :value="$values['timezone']" :options="collect(timezone_identifiers_list())->mapWithKeys(fn ($zone) => [$zone => $zone])->all()" required />
                <div><label class="block text-xs font-semibold text-cine-secondary">Currency</label><input value="USD" readonly class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-elevated px-3 text-sm text-cine-secondary"><p class="mt-1 text-xs text-cine-tertiary">Checkout and payments are currently fixed to USD.</p></div>
                <input type="hidden" name="values[currency]" value="USD">
                <div class="md:col-span-2"><x-admin.button type="submit" x-bind:disabled="saving">Save general settings</x-admin.button></div>
            </form>
            @if($updatedBy)<p class="mt-4 text-xs text-cine-tertiary">Last updated by {{ $updatedBy }}.</p>@endif
        </x-admin.card>
    @elseif($tab === 'rules')
        <x-admin.card>
            <h2 class="text-lg font-semibold text-cine-primary">Booking rules</h2>
            <p class="mt-1 text-sm text-cine-secondary">Current validated limits are persisted and audited. Customer cancellation remains limited to at least 2 hours before showtime and receives a 100% refund.</p>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-5 grid gap-4 md:grid-cols-2" x-data="{saving:false}" x-on:submit="if(saving){$event.preventDefault();return} saving=true">@csrf @method('PATCH')
                @foreach(['seat_hold_minutes' => ['Hold duration (minutes)', 10], 'max_seats_per_booking' => ['Maximum seats per booking', 6], 'sales_close_minutes' => ['Sales cutoff (minutes)', 15], 'customer_cancel_hours' => ['Cancellation window (hours)', 2], 'refund_percent' => ['Cancellation refund percent', 100]] as $key => [$label, $fixed])
                    <div><label for="setting-{{ $key }}" class="block text-xs font-semibold text-cine-secondary">{{ $label }}</label><input id="setting-{{ $key }}" name="values[{{ $key }}]" value="{{ $values[$key] ?? $fixed }}" readonly class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-elevated px-3 text-sm text-cine-secondary">@error('values.'.$key)<span class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror</div>
                @endforeach
                <p class="text-xs text-cine-tertiary md:col-span-2">The current application policy supports these validated values only.</p>
                <div class="md:col-span-2"><x-admin.button type="submit" x-bind:disabled="saving">Save booking rules</x-admin.button></div>
            </form>
        </x-admin.card>
    @elseif($tab === 'payments')
        <x-admin.card><h2 class="text-lg font-semibold text-cine-primary">Payments</h2><p class="mt-1 text-sm text-cine-secondary">Stripe configuration is read-only here. Secrets are masked and are never written to the database.</p><dl class="mt-5 grid gap-4 md:grid-cols-2"><div><dt class="text-xs font-semibold text-cine-tertiary">Mode</dt><dd class="mt-1 text-cine-primary">{{ $payment['mode'] }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Secret key</dt><dd class="mt-1 font-mono text-cine-primary">{{ $payment['secret'] }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Publishable key</dt><dd class="mt-1 font-mono text-cine-primary">{{ $payment['publishable'] }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Webhook secret</dt><dd class="mt-1 font-mono text-cine-primary">{{ $payment['webhook'] }}</dd></div></dl></x-admin.card>
    @else
        <x-admin.card><h2 class="text-lg font-semibold text-cine-primary">Emails</h2><p class="mt-1 text-sm text-cine-secondary">Mail transport is configured through environment settings and cannot be edited here.</p><dl class="mt-5 grid gap-4 md:grid-cols-2"><div><dt class="text-xs font-semibold text-cine-tertiary">From name</dt><dd class="mt-1 text-cine-primary">{{ $email['from_name'] }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">From address</dt><dd class="mt-1 text-cine-primary">{{ $email['from_address'] }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Queue connection</dt><dd class="mt-1 text-cine-primary">{{ $email['queue_connection'] }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Retry after</dt><dd class="mt-1 text-cine-primary">{{ $email['retry_after'] ?? 'Not configured' }} seconds</dd></div></dl><p class="mt-4 text-xs text-cine-tertiary">Run <code>php artisan queue:work</code> to process queued messages and gateway operations.</p></x-admin.card>
    @endif
</x-admin.layout>
