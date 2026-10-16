@props(['items', 'label' => 'Chart'])

<div role="img" aria-label="{{ $label }}" class="flex h-40 items-end gap-2">
    @foreach ($items as $item)
        <div class="group flex min-w-0 flex-1 flex-col justify-end" title="{{ $item['label'] ?? '' }}: {{ $item['value'] ?? 0 }}">
            <span class="mx-auto mb-1 text-[10px] text-cine-secondary">{{ $item['value'] ?? 0 }}</span>
            <div class="rounded-t bg-cine-accent" style="height: {{ max(2, min(100, (int) ($item['percent'] ?? 0))) }}%"></div>
            <span class="mt-1 truncate text-center text-[10px] text-cine-tertiary">{{ $item['label'] ?? '' }}</span>
        </div>
    @endforeach
</div>
