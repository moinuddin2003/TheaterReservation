@props([
    'label',
    'route',
    'icon',
    'badge' => null,
])

@php
    $activePattern = preg_replace('/\.(index|edit)$/', '', $route).'.*';
    $active = request()->routeIs($route, $activePattern);
@endphp

<a
    href="{{ route($route) }}"
    @if ($active) aria-current="page" @endif
    :title="collapsed && !mobileOpen ? @js($label) : null"
    class="group flex min-h-11 items-center gap-3 rounded-xl border px-3 text-sm font-medium transition-[background-color,border-color,color,box-shadow] duration-150 ease-out motion-reduce:transition-none focus-visible:shadow-cine-focus"
    :class="{
        'justify-center px-0': collapsed && !mobileOpen,
        'border-cine-accent bg-gradient-to-r from-cine-selected-start to-cine-selected-end text-white shadow-cine-glow': @js($active),
        'border-transparent text-cine-secondary hover:border-cine-border/10 hover:bg-cine-surface hover:text-cine-primary': !@js($active),
    }"
>
    <x-admin.icon :name="$icon" class="h-[18px] w-[18px]" />
    <span x-show="!collapsed || mobileOpen" x-transition.opacity class="min-w-0 flex-1 truncate">{{ $label }}</span>
    @if ($badge !== null)
        <span x-show="!collapsed || mobileOpen" class="rounded-full border border-cine-warning/20 bg-cine-warning/10 px-2 py-0.5 text-[10px] font-semibold text-cine-warning">
            {{ $badge }}
        </span>
    @endif
</a>
