@props(['showtime'])

<div x-data="movieActions" class="flex justify-end">
    <button
        type="button"
        aria-label="More actions for {{ $showtime->movie->title }} at {{ $showtime->starts_at->format('M j, h:i A') }}"
        popovertarget="showtime-actions-{{ $showtime->id }}"
        @click.prevent="open($refs.menu, $event.currentTarget)"
        class="flex h-8 w-8 items-center justify-center rounded-lg border border-cine-border/10 bg-cine-bg text-cine-secondary transition hover:border-cine-accent/30 hover:text-cine-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cine-accent/50"
    >
        <x-admin.icon name="more-vertical" class="h-4 w-4" />
    </button>
    <div
        id="showtime-actions-{{ $showtime->id }}"
        x-ref="menu"
        popover="auto"
        class="!fixed !m-0 z-[100] w-44 rounded-xl border border-cine-border/10 bg-cine-surface p-1.5 text-cine-primary shadow-cine-card backdrop:bg-transparent"
    >
        @if ($showtime->isScheduled())
            <a
                href="{{ route('admin.showtimes.edit', $showtime) }}"
                class="block rounded-lg px-3 py-2 text-left text-xs font-medium text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary"
            >
                Edit showtime
            </a>
            @if (! $showtime->hasEnded())
                <a
                    href="{{ route('admin.showtimes.cancel.confirm', $showtime) }}"
                    class="block rounded-lg px-3 py-2 text-left text-xs font-medium text-cine-danger transition hover:bg-cine-danger/10"
                >
                    Cancel showtime
                </a>
            @endif
        @else
            <span class="block px-3 py-2 text-xs text-cine-tertiary">No actions available</span>
        @endif
    </div>
</div>
