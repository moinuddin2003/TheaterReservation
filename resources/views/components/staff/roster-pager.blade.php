@props(['roster'])

@php
    $link = fn (int $page): string => route('staff.roster', array_filter([
        'show' => $roster['selected']['id'],
        'q' => $roster['query'] ?: null,
        'page' => $page > 1 ? $page : null,
    ]));
    $hasPrevious = $roster['page'] > 1;
    $hasNext = $roster['page'] < $roster['pages'];
@endphp

<nav class="roster-pager" aria-label="Roster pages">
    <p class="roster-pager__count">
        @if ($roster['total'] === 0)
            No bookings to show
        @else
            Showing {{ $roster['from'] }}–{{ $roster['to'] }} of {{ $roster['total'] }} {{ Str::plural('booking', $roster['total']) }}
        @endif
    </p>

    <div class="roster-pager__pages">
        @if ($hasPrevious)
            <a class="button button--quiet" href="{{ $link($roster['page'] - 1) }}" rel="prev">← Previous</a>
        @else
            <span class="button button--quiet is-disabled" aria-disabled="true">← Previous</span>
        @endif
        <span class="roster-pager__page" aria-label="Page {{ $roster['page'] }} of {{ $roster['pages'] }}">• {{ $roster['page'] }}</span>
    </div>

    @if ($hasNext)
        <a class="button button--quiet roster-pager__next" href="{{ $link($roster['page'] + 1) }}" rel="next">Next →</a>
    @else
        <span class="button button--quiet roster-pager__next is-disabled" aria-disabled="true">Next →</span>
    @endif
</nav>
