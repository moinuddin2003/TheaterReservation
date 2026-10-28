<x-admin.layout title="Audit log" breadcrumb="Operations">
    <x-admin.page-header title="Audit log" subtitle="Immutable operational history. No edit or delete actions." />

    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-4">
        <input name="actor" value="{{ $actor }}" placeholder="Actor" class="rounded-lg border border-cine-border/10 bg-cine-surface px-3 py-3 text-xs text-cine-primary placeholder:text-cine-tertiary">
        <input name="action" value="{{ $action }}" placeholder="Action, e.g. settings.updated" class="rounded-lg border border-cine-border/10 bg-cine-surface px-3 py-3 text-xs text-cine-primary placeholder:text-cine-tertiary">
        <input name="target" value="{{ $target }}" placeholder="Search target or ID" class="rounded-lg border border-cine-border/10 bg-cine-surface px-3 py-3 text-xs text-cine-primary placeholder:text-cine-tertiary">
        <div class="flex gap-2">
            <input type="date" name="date" value="{{ $date }}" class="min-w-0 flex-1 rounded-lg border border-cine-border/10 bg-cine-surface px-3 py-3 text-xs text-cine-primary">
            <x-admin.button type="submit">Filter</x-admin.button>
        </div>
    </form>

    <x-admin.card class="overflow-hidden !p-0">
        <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-xs">
            <thead class="border-b border-cine-border/10 text-[10px] uppercase tracking-wider text-cine-tertiary"><tr><th class="px-5 py-4">Time</th><th class="px-5 py-4">Actor</th><th class="px-5 py-4">Action</th><th class="px-5 py-4">Target</th></tr></thead>
            <tbody class="divide-y divide-cine-border/10">
                @forelse ($logs as $log)
                    <tr class="text-cine-secondary">
                        <td class="px-5 py-4">{{ $log->created_at?->format('H:i') }}</td>
                        <td class="px-5 py-4">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-5 py-4 font-medium text-cine-accent">{{ $log->action }}</td>
                        <td class="px-5 py-4"><a href="{{ request()->fullUrlWithQuery(['log' => $log->id]) }}" class="text-cine-primary hover:text-cine-accent">{{ class_basename($log->auditable_type) }}{{ isset($log->after['key']) ? ' · '.$log->after['key'] : ' #'.$log->auditable_id }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-14"><x-admin.empty-state icon="scroll-text" title="No audit entries" text="Recorded administrative actions will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-admin.card>
    <div class="mt-4">{{ $logs->links() }}</div>
    <div class="mt-4 rounded-md bg-[#183663] px-3 py-3 text-xs leading-5 text-cine-primary">ⓘ Immutable operational history<br>Read-only audit records. No edit or delete actions. Secrets, payment tokens and password data are never shown.</div>

    @if ($selectedLog)
        <div class="fixed inset-0 z-[100] bg-black/60 p-4" x-data @keydown.escape.window="$el.remove()">
            <div class="ml-auto h-full w-full max-w-[360px] overflow-y-auto rounded-xl border border-cine-border/10 bg-cine-surface p-5 shadow-cine-card">
                <div class="flex items-center justify-between"><p class="text-[10px] uppercase tracking-wider text-cine-accent">Detail drawer</p><a href="{{ request()->fullUrlWithQuery(['log' => null]) }}" class="text-cine-primary">×</a></div>
                <h2 class="mt-5 text-lg font-bold text-cine-primary">{{ $selectedLog->action }}</h2>
                <dl class="mt-5 space-y-4 text-xs"><div><dt class="text-cine-tertiary">Actor</dt><dd class="mt-1 text-cine-secondary">{{ $selectedLog->user?->name ?? 'System' }}</dd></div><div><dt class="text-cine-tertiary">Target</dt><dd class="mt-1 text-cine-secondary">{{ class_basename($selectedLog->auditable_type) }}{{ isset($selectedLog->after['key']) ? ' · '.$selectedLog->after['key'] : ' #'.$selectedLog->auditable_id }}</dd></div><div><dt class="text-cine-tertiary">Time</dt><dd class="mt-1 text-cine-secondary">{{ $selectedLog->created_at?->format('M j, Y · H:i:s') }}</dd></div><div><dt class="text-cine-tertiary">IP address</dt><dd class="mt-1 text-cine-secondary">{{ $selectedLog->ip_address ?? '—' }}</dd></div></dl>
                <div class="mt-6 rounded-lg border border-cine-border/10 p-3"><p class="text-[10px] uppercase text-cine-tertiary">Before / after</p><pre class="mt-3 whitespace-pre-wrap text-[11px] leading-5 text-cine-secondary">{{ json_encode(['before' => $selectedLog->before, 'after' => $selectedLog->after], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
            </div>
        </div>
    @endif
</x-admin.layout>
