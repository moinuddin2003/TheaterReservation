@props(['label', 'value', 'note' => null, 'icon' => 'ticket', 'trend' => null])

<x-admin.card class="transition-colors hover:border-cine-accent/30">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-medium text-cine-secondary">{{ $label }}</p>
            <p class="mt-3 text-2xl font-bold tracking-tight text-cine-primary">{{ $value }}</p>
            @if ($note)
                <p class="mt-2 text-xs text-cine-tertiary">{{ $note }}</p>
            @endif
        </div>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
            <x-admin.icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</x-admin.card>
