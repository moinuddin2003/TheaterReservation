@props(['title' => null, 'subtitle' => null])

<section {{ $attributes->class(['rounded-2xl border border-cine-border/10 bg-cine-surface shadow-cine-card']) }}>
    @if ($title || isset($header))
        <div class="border-b border-cine-border/10 px-5 py-4 sm:px-6">
            @if (isset($header))
                {{ $header }}
            @else
                <h3 class="font-semibold text-cine-primary">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="mt-1 text-sm text-cine-secondary">{{ $subtitle }}</p>
                @endif
            @endif
        </div>
    @endif
    <div class="p-5 sm:p-6">{{ $slot }}</div>
</section>
