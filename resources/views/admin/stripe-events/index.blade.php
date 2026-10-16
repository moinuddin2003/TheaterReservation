<x-admin.layout title="Stripe Events" breadcrumb="System">
    <x-admin.page-header title="Stripe Events" subtitle="Review webhook processing results and replay failed events." />
    <x-admin.toast />
    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4 sm:grid-cols-2 xl:grid-cols-5">
        <label class="text-xs font-semibold text-cine-secondary">Search<input name="q" value="{{ request('q') }}" placeholder="Event ID, type, error" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">Event type<select name="type" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All types</option>@foreach($types as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">Status<select name="status" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All statuses</option>@foreach(['received', 'processed', 'failed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->title() }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">Sort by<select name="sort" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">@foreach(['created_at' => 'Received', 'type' => 'Type', 'status' => 'Status'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'created_at') === $key)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">Direction<select name="direction" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('direction') === 'asc')>Ascending</option></select></label>
        <div class="flex items-end gap-2"><x-admin.button type="submit">Apply</x-admin.button><x-admin.button href="{{ route('admin.stripe-events.index') }}" variant="secondary">Clear</x-admin.button></div>
    </form>
    <x-admin.data-table :rows="$events" :columns="[
        ['label' => 'Event ID', 'slot' => fn ($row) => substr($row->stripe_event_id, 0, 14).'…'.substr($row->stripe_event_id, -6)],
        ['label' => 'Type', 'key' => 'type'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Received', 'slot' => fn ($row) => $row->created_at->timezone($timezone)->format('M j, Y g:i:s A')],
        ['label' => 'Error', 'slot' => fn ($row) => \Illuminate\Support\Str::limit($row->error_message, 80) ?: '—'],
        ['label' => 'Details', 'type' => 'link', 'route' => 'admin.stripe-events.show'],
    ]" empty-title="No Stripe events" empty-text="Webhook events will appear here when received." />
</x-admin.layout>
