@props(['action' => null])

<div class="mb-5">
    <form method="GET" action="{{ $action }}" x-data="adminSearch" class="flex flex-wrap items-end gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4">
        <label class="min-w-48 flex-1 text-xs font-semibold text-cine-secondary">
            Search
            <input name="q" value="{{ request('q') }}" placeholder="Search records…" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary placeholder:text-cine-tertiary">
        </label>
        {{ $slot }}
        <x-admin.button type="submit">Apply</x-admin.button>
        @if (request()->query())
            <a href="{{ $action }}" class="inline-flex min-h-10 items-center rounded-xl px-3 text-sm font-semibold text-cine-secondary hover:bg-cine-elevated hover:text-cine-primary">Clear all</a>
        @endif
    </form>
    @php($activeFilters = collect(request()->query())->reject(fn ($value, $key) => in_array($key, ['page', 'per_page'], true) || $value === '' || $value === null))
    @if ($activeFilters->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-2" aria-label="Active filters">
            @foreach ($activeFilters as $key => $value)
                @if (is_scalar($value))
                    <a href="{{ request()->fullUrlWithoutQuery([$key, 'page']) }}" class="inline-flex items-center gap-2 rounded-full border border-cine-accent/20 bg-cine-accent/10 px-3 py-1 text-xs font-medium text-cine-accent hover:bg-cine-accent/20">
                        {{ str($key)->replace('_', ' ')->title() }}: {{ $value }} <span aria-hidden="true">×</span>
                    </a>
                @endif
            @endforeach
        </div>
    @endif
</div>
