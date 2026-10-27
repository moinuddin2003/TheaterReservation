@props([
    'title',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => null,
])

<div class="mb-7 flex flex-col gap-4 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if ($backUrl && $backLabel)
            <a href="{{ $backUrl }}" class="mb-4 inline-flex items-center gap-2 text-xs font-semibold text-cine-secondary transition hover:text-cine-primary">
                <x-admin.icon name="arrow-left" class="h-4 w-4" />
                {{ $backLabel }}
            </a>
        @endif
        <h2 class="text-2xl font-bold tracking-tight text-cine-primary sm:text-3xl">{{ $title }}</h2>
        @if (isset($meta))
            <div class="mt-2">{{ $meta }}</div>
        @endif
        @if ($subtitle)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-cine-secondary">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex shrink-0 items-center gap-3">{{ $actions }}</div>
    @endif
</div>
