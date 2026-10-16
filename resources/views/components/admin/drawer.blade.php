@props(['name', 'title', 'id' => null])

@php($drawerId = $id ?? $name)

<div x-cloak x-show="{{ $name }}" x-on:keydown.escape.window="{{ $name }} = false" class="fixed inset-0 z-[65] flex justify-end bg-cine-bg/70" role="dialog" aria-modal="true" aria-labelledby="{{ $drawerId }}-title">
    <button type="button" class="absolute inset-0 cursor-default" aria-label="Close drawer" x-on:click="{{ $name }} = false"></button>
    <aside x-trap.noscroll="{{ $name }}" x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="relative h-full w-full max-w-xl overflow-y-auto border-l border-cine-border/10 bg-cine-surface p-5 shadow-cine-card sm:p-7">
        <div class="mb-6 flex items-center justify-between">
            <h2 id="{{ $drawerId }}-title" class="text-lg font-semibold text-cine-primary">{{ $title }}</h2>
            <button type="button" aria-label="Close drawer" class="rounded-lg p-2 text-cine-secondary hover:bg-cine-elevated" x-on:click="{{ $name }} = false">×</button>
        </div>
        {{ $slot }}
    </aside>
</div>
