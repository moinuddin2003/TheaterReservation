<x-admin.layout title="Payments" breadcrumb="Sales">
    <x-admin.page-header title="Payments" subtitle="Read-only payment records with booking links and gateway references." />
    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4 sm:grid-cols-2 xl:grid-cols-5">
        <label class="text-xs font-semibold text-cine-secondary">Search<input name="q" value="{{ request('q') }}" placeholder="Payment intent or booking code" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">Status<select name="status" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All statuses</option>@foreach (\App\Enums\PaymentStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str($status->value)->title() }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">From<input type="date" name="from" value="{{ request('from') }}" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">To<input type="date" name="to" value="{{ request('to') }}" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">Sort by<select name="sort" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">@foreach(['created_at' => 'Date', 'status' => 'Status', 'amount_cents' => 'Amount'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'created_at') === $key)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">Direction<select name="direction" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('direction') === 'asc')>Ascending</option></select></label>
        <div class="flex items-end gap-2"><x-admin.button type="submit">Filter</x-admin.button><x-admin.button href="{{ route('admin.payments.index') }}" variant="secondary">Clear</x-admin.button></div>
    </form>
    <div class="mb-4 grid gap-3 sm:grid-cols-2"><x-admin.stat-card label="Matching payments" :value="number_format($totals->payment_count)" icon="credit-card" /><x-admin.stat-card label="Matching total" :value="'$'.number_format($totals->amount_total / 100, 2)" icon="dollar-sign" /></div>
    <x-admin.data-table :rows="$payments" :columns="[
        ['label' => 'Booking', 'slot' => fn ($row) => $row->booking->reference],
        ['label' => 'Gateway IDs', 'slot' => fn ($row) => $row->gateway.' · '.($row->gateway_payment_intent_id ? '…'.substr($row->gateway_payment_intent_id, -8) : '—')],
        ['label' => 'Customer', 'slot' => fn ($row) => $row->booking->user->name],
        ['label' => 'Amount', 'slot' => fn ($row) => '$'.number_format($row->amount_cents / 100, 2).' '.$row->currency],
        ['label' => 'Status', 'slot' => fn ($row) => $row->status->value],
        ['label' => 'Date', 'slot' => fn ($row) => $row->created_at->timezone($timezone)->format('M j, Y g:i A')],
        ['label' => 'Details', 'type' => 'link', 'route' => 'admin.payments.show'],
    ]" empty-title="No payments found" empty-text="Try a different search or date range.">
    </x-admin.data-table>
</x-admin.layout>
