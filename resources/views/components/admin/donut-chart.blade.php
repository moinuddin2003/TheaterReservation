@props(['values', 'label' => 'Distribution'])

@php
    $total = max(1, collect($values)->sum('value'));
    $segments = collect($values)->values();
    $offset = 0;
    $gradient = $segments->map(function ($segment, $index) use ($total, &$offset) {
        $start = $offset;
        $offset += (int) round(($segment['value'] / $total) * 100);
        $color = $index % 2 === 0 ? 'rgb(var(--cine-accent))' : 'rgb(var(--cine-success))';
        return "{$color} {$start}% {$offset}%";
    })->implode(', ');
@endphp
<div class="flex items-center gap-5" role="img" aria-label="{{ $label }}">
    <div class="h-28 w-28 shrink-0 rounded-full" style="background: conic-gradient({{ $gradient }})">
        <div class="m-4 flex h-20 w-20 items-center justify-center rounded-full bg-cine-surface text-sm font-bold text-cine-primary">{{ array_sum(array_column($values, 'value')) }}</div>
    </div>
    <ul class="space-y-2 text-xs text-cine-secondary">
        @foreach ($values as $value)
            <li class="flex justify-between gap-4"><span>{{ $value['label'] }}</span><strong class="text-cine-primary">{{ $value['value'] }}</strong></li>
        @endforeach
    </ul>
</div>
