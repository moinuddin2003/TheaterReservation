<x-admin.layout title="Audit log #{{ $log->id }}" breadcrumb="Audit Logs">
    <x-admin.page-header title="Audit entry #{{ $log->id }}" subtitle="{{ $log->action }}">
        <x-slot:actions><x-admin.button href="{{ route('admin.audit-logs.index') }}" variant="secondary">Back to audit logs</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.card>
        <dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-xs font-semibold text-cine-tertiary">Actor</dt><dd class="mt-1 text-sm text-cine-primary">{{ $log->user?->name ?? 'System' }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Action</dt><dd class="mt-1 text-sm text-cine-primary">{{ $log->action }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Entity</dt><dd class="mt-1 text-sm text-cine-primary">{{ $log->auditable_type }} #{{ $log->auditable_id }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">IP / Time</dt><dd class="mt-1 text-sm text-cine-primary">{{ $log->ip_address ?? '—' }} · {{ $log->created_at->timezone($timezone)->format('M j, Y g:i:s A') }}</dd></div></dl>
        <div class="mt-6 grid gap-4 lg:grid-cols-2"><section><h2 class="mb-2 font-semibold text-cine-primary">Before</h2><pre class="max-h-[60vh] overflow-auto rounded-xl bg-cine-bg p-4 text-xs text-cine-primary">{{ json_encode($log->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'No before snapshot' }}</pre></section><section><h2 class="mb-2 font-semibold text-cine-primary">After</h2><pre class="max-h-[60vh] overflow-auto rounded-xl bg-cine-bg p-4 text-xs text-cine-primary">{{ json_encode($log->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'No after snapshot' }}</pre></section></div>
    </x-admin.card>
</x-admin.layout>
