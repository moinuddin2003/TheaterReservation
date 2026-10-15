@props(['title', 'subtitle' => null])

<div class="mb-7 flex flex-col gap-4 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.22em] text-cine-accent">CineBook · Admin</p>
        <h2 class="text-2xl font-bold tracking-tight text-cine-primary sm:text-3xl">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-cine-secondary">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex shrink-0 items-center gap-3">{{ $actions }}</div>
    @endif
</div>
