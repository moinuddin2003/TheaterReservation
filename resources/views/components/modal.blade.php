@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'title' => 'Confirmation',
    'panelClass' => '',
])

@php
    $maxWidth = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth];
@endphp

<div
    x-data="adminModal(@js($show))"
    x-on:open-modal.window="$event.detail === '{{ $name }}' && open()"
    x-on:close-modal.window="$event.detail === '{{ $name }}' && close()"
    x-on:keydown.escape.window="if (show) { $event.stopPropagation(); close(); }"
    x-on:keydown.tab="if (show) trapFocus($event)"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[150] overflow-y-auto px-4 py-6 sm:px-0"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    <button
        type="button"
        tabindex="-1"
        aria-label="Close dialog"
        x-show="show"
        x-on:click="close()"
        class="fixed inset-0 h-full w-full cursor-default bg-cine-deep/85 backdrop-blur-sm"
    ></button>

    <div
        x-ref="panel"
        x-show="show"
        role="dialog"
        aria-modal="true"
        aria-label="{{ $title }}"
        tabindex="-1"
        x-on:click.stop
        class="relative mx-auto mb-6 w-full overflow-hidden rounded-2xl border border-cine-border/15 bg-cine-surface shadow-cine-card {{ $maxWidth }} {{ $panelClass }}"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="translate-y-3 scale-[.98] opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
        x-transition:leave-end="translate-y-2 scale-[.98] opacity-0"
    >
        {{ $slot }}
    </div>
</div>
