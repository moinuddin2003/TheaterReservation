@props(['lines' => 3])

<div class="animate-pulse space-y-3" aria-label="Loading" role="status">
    @foreach (range(1, $lines) as $line)
        <div class="h-4 rounded-lg bg-cine-elevated {{ $line === 1 ? 'w-2/3' : 'w-full' }}"></div>
    @endforeach
    <span class="sr-only">Loading content…</span>
</div>
