@props(['items', 'active'])

<nav class="mb-5 flex gap-2 overflow-x-auto border-b border-cine-border/10" aria-label="Sections">
    @foreach ($items as $key => $label)
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}" @if ($active === $key) aria-current="page" @endif class="whitespace-nowrap border-b-2 px-3 py-2 text-sm font-semibold {{ $active === $key ? 'border-cine-accent text-cine-primary' : 'border-transparent text-cine-secondary hover:text-cine-primary' }}">{{ $label }}</a>
    @endforeach
</nav>
