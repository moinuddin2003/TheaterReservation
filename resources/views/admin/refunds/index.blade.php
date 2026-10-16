<x-admin.layout title="Refunds" breadcrumb="Sales">
    <x-admin.page-header title="Refunds" subtitle="Track requested refunds and process failed or pending gateway operations." />
    <x-admin.toast />
    <nav class="mb-5 flex flex-wrap gap-2" aria-label="Refund status">
        @foreach (['pending' => 'Pending', 'succeeded' => 'Succeeded', 'failed' => 'Failed'] as $value => $label)
            <a href="{{ route('admin.refunds.index', array_merge(request()->except('page'), ['status' => $value])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $status === $value ? 'border-cine-accent/50 bg-cine-accent/10 text-cine-accent' : 'border-cine-border/10 bg-cine-surface text-cine-secondary hover:text-cine-primary' }}">{{ $label }} <span class="ml-1">{{ number_format($counts[$value] ?? 0) }}</span></a>
        @endforeach
    </nav>
    <form method="GET" class="mb-5 flex flex-wrap gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4">
        <input type="hidden" name="status" value="{{ $status }}">
        <label class="min-w-48 flex-1 text-xs font-semibold text-cine-secondary">Search<input name="q" value="{{ request('q') }}" placeholder="Booking, customer or reason" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">From<input type="date" name="from" value="{{ request('from') }}" class="mt-1.5 block min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">To<input type="date" name="to" value="{{ request('to') }}" class="mt-1.5 block min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">Sort by<select name="sort" class="mt-1.5 block min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">@foreach(['created_at' => 'Requested', 'status' => 'Status', 'amount_cents' => 'Amount'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'created_at') === $key)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">Direction<select name="direction" class="mt-1.5 block min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('direction') === 'asc')>Ascending</option></select></label>
        <div class="flex items-end gap-2"><x-admin.button type="submit">Apply</x-admin.button><x-admin.button href="{{ route('admin.refunds.index', ['status' => $status]) }}" variant="secondary">Clear</x-admin.button></div>
    </form>
    <x-admin.data-table :rows="$refunds" :columns="[
        ['label' => 'Booking', 'slot' => fn ($row) => $row->booking->reference],
        ['label' => 'Customer', 'slot' => fn ($row) => $row->booking->user->name],
        ['label' => 'Amount', 'slot' => fn ($row) => '$'.number_format($row->amount_cents / 100, 2).' '.$row->currency],
        ['label' => 'Reason', 'key' => 'reason'],
        ['label' => 'Status', 'slot' => fn ($row) => $row->status->value],
        ['label' => 'Requested', 'slot' => fn ($row) => $row->created_at->timezone($timezone)->format('M j, Y g:i A')],
        ['label' => 'Details', 'type' => 'link', 'route' => 'admin.refunds.show'],
    ]" empty-title="No refunds in this view" empty-text="Choose a different status or adjust the search.">
    </x-admin.data-table>
</x-admin.layout>
