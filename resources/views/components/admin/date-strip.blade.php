@props(['days', 'selected'])

<nav class="mb-5 grid grid-cols-4 gap-2 sm:grid-cols-7" aria-label="Select a week date">
    @foreach ($days as $day)
        <a href="{{ request()->fullUrlWithQuery(['date' => $day->toDateString()]) }}" @if ($day->isSameDay($selected)) aria-current="date" @endif class="rounded-xl border px-2 py-3 text-center transition-colors {{ $day->isSameDay($selected) ? 'border-cine-accent/50 bg-cine-accent/15 text-cine-primary' : 'border-cine-border/10 bg-cine-surface text-cine-secondary hover:bg-cine-elevated' }}">
            <span class="block text-xs">{{ $day->format('D') }}</span>
            <span class="mt-1 block text-lg font-semibold">{{ $day->format('j') }}</span>
        </a>
    @endforeach
</nav>
