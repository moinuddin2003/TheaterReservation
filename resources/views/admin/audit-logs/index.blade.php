<x-admin.layout title="Audit Logs" breadcrumb="System">
    <x-admin.page-header title="Audit Logs" subtitle="Search audited changes by actor, action, entity, and date." />
    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4 sm:grid-cols-2 xl:grid-cols-4">
        <label class="text-xs font-semibold text-cine-secondary">Search<input name="q" value="{{ request('q') }}" placeholder="Actor, action or entity" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">Actor<select name="actor" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All actors</option>@foreach($actors as $actor)<option value="{{ $actor->id }}" @selected(request('actor') == $actor->id)>{{ $actor->name }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">Entity<select name="entity" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All entities</option>@foreach($entities as $entity)<option value="{{ $entity }}" @selected(request('entity') === $entity)>{{ $entity }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">Action<select name="action" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All actions</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">From<input type="date" name="from" value="{{ request('from') }}" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">To<input type="date" name="to" value="{{ request('to') }}" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">Sort by<select name="sort" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">@foreach(['created_at' => 'Time', 'action' => 'Action', 'auditable_type' => 'Entity'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'created_at') === $key)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-cine-secondary">Direction<select name="direction" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('direction') === 'asc')>Ascending</option></select></label>
        <div class="flex items-end gap-2"><x-admin.button type="submit">Apply</x-admin.button><x-admin.button href="{{ route('admin.audit-logs.index') }}" variant="secondary">Clear</x-admin.button></div>
    </form>
    <x-admin.data-table :rows="$logs" :columns="[
        ['label' => 'Actor', 'slot' => fn ($row) => $row->user?->name ?? 'System'],
        ['label' => 'Action', 'key' => 'action'],
        ['label' => 'Entity', 'slot' => fn ($row) => $row->auditable_type.' #'.$row->auditable_id],
        ['label' => 'IP address', 'key' => 'ip_address'],
        ['label' => 'Time', 'slot' => fn ($row) => $row->created_at->timezone($timezone)->format('M j, Y g:i:s A')],
        ['label' => 'Diff', 'type' => 'link', 'route' => 'admin.audit-logs.show'],
    ]" empty-title="No audit logs" empty-text="No audit records match the selected filters." />
</x-admin.layout>
