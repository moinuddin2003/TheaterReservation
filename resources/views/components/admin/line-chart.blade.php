@props(['points', 'label' => 'Revenue'])

@php
    $values = collect($points)->pluck('amount_cents')->all();
    $max = max(1, ...$values);
    $count = max(1, count($values) - 1);
    $coords = collect($values)->map(fn ($value, $index) => ($index * 300 / $count).','.((100 - ((int) $value / $max * 90))));
@endphp
<svg viewBox="0 0 300 110" role="img" aria-label="{{ $label }} chart" class="h-48 w-full overflow-visible text-cine-accent">
    <polyline points="{{ $coords->implode(' ') }}" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
</svg>
