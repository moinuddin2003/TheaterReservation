@props([
    'action',
    'label' => 'Filters',
    'width' => 'w-[min(92vw,460px)]',
])

<details class="group relative">
    <summary class="flex min-h-10 cursor-pointer list-none items-center gap-2 rounded-xl border border-cine-border/10 bg-cine-surface px-3 text-xs font-semibold text-cine-secondary transition hover:border-cine-accent/30 hover:text-cine-primary [&::-webkit-details-marker]:hidden">
        <x-admin.icon name="filter" class="h-4 w-4 text-cine-accent" />
        <span>{{ $label }}</span>
        <x-admin.icon name="chevron-down" class="h-3.5 w-3.5 transition group-open:rotate-180" />
    </summary>
    <form method="GET" action="{{ $action }}" class="absolute right-0 top-full z-20 mt-2 {{ $width }} rounded-2xl border border-cine-border/10 bg-cine-surface p-4 shadow-cine-card">
        {{ $slot }}
    </form>
</details>
