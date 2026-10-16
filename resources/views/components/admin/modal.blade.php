@props(['name', 'title', 'id' => null])

@php($modalId = $id ?? $name)

<div x-cloak x-show="{{ $name }}" x-on:keydown.escape.window="{{ $name }} = false" x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center overflow-y-auto bg-cine-bg/80 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <button type="button" class="fixed inset-0 cursor-default" aria-label="Close dialog" x-on:click="{{ $name }} = false"></button>
    <section x-trap.noscroll="{{ $name }}" x-transition class="relative z-10 w-full max-w-xl rounded-2xl border border-cine-border/10 bg-cine-surface p-5 shadow-cine-card sm:p-6">
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-cine-primary">{{ $title }}</h2>
            <button type="button" aria-label="Close dialog" class="rounded-lg p-2 text-cine-secondary hover:bg-cine-elevated" x-on:click="{{ $name }} = false">×</button>
        </div>
        {{ $slot }}
    </section>
</div>
