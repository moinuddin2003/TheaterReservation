<x-admin.layout :title="$pageTitle" :breadcrumb="$breadcrumb">
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle" />
    @if (session('status'))
        <div role="status" class="mb-4 rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ $errors->first() }}</div>
    @endif
    <form method="GET" class="mb-4 flex flex-wrap gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4">
        <input name="q" value="{{ request('q') }}" placeholder="Search..." class="min-w-48 flex-1 rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2.5 text-sm text-cine-primary">
        @foreach ($filters ?? [] as $filter)
            <select name="{{ $filter['name'] }}" class="rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2.5 text-sm text-cine-primary">
                <option value="">All {{ $filter['label'] }}</option>
                @foreach ($filter['options'] as $value => $label)
                    <option value="{{ $value }}" @selected(request($filter['name']) == $value)>{{ $label }}</option>
                @endforeach
            </select>
        @endforeach
        <select name="sort" class="rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2.5 text-sm text-cine-primary">
            @foreach ($sortOptions ?? [] as $value => $label)
                <option value="{{ $value }}" @selected(request('sort') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="direction" class="rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2.5 text-sm text-cine-primary">
            <option value="desc" @selected(request('direction', 'desc') === 'desc')>Newest</option>
            <option value="asc" @selected(request('direction') === 'asc')>Oldest</option>
        </select>
        <button class="rounded-xl bg-cine-accent px-4 py-2.5 text-sm font-semibold text-white">Apply</button>
    </form>
    <div class="overflow-hidden rounded-2xl border border-cine-border/10 bg-cine-surface">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-cine-elevated/60 text-xs uppercase tracking-wider text-cine-tertiary"><tr>
                    @foreach ($columns as $column)<th class="whitespace-nowrap px-4 py-3 font-semibold">{{ $column['label'] }}</th>@endforeach
                    @if (! empty($detailRoute))<th class="px-4 py-3"></th>@endif
                </tr></thead>
                <tbody class="divide-y divide-cine-border/10">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-cine-elevated/30">
                            @foreach ($columns as $column)
                                @php($value = data_get($row, $column['key']))
                                <td class="whitespace-nowrap px-4 py-3 text-cine-primary">
                                    @if (($column['type'] ?? '') === 'status')
                                        <span class="rounded-full bg-cine-elevated px-2.5 py-1 text-xs">{{ str_replace('_', ' ', $value instanceof \BackedEnum ? $value->value : $value) }}</span>
                                    @elseif (($column['type'] ?? '') === 'money')
                                        ${{ number_format(((int) $value) / 100, 2) }}
                                    @elseif ($value instanceof \DateTimeInterface)
                                        {{ $value->format('M j, Y g:i A') }}
                                    @elseif ($value instanceof \BackedEnum)
                                        {{ str_replace('_', ' ', $value->value) }}
                                    @elseif ($value instanceof \Illuminate\Support\Collection)
                                        {{ $value->pluck($column['property'] ?? 'name')->implode(', ') }}
                                    @else
                                        {{ $value ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                            @if (! empty($detailRoute))
                                <td class="px-4 py-3 text-right"><a class="font-semibold text-cine-accent" href="{{ route($detailRoute, $row->getKey()) }}">Details</a></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) + (empty($detailRoute) ? 0 : 1) }}" class="px-4 py-12 text-center text-sm text-cine-tertiary">No records found. Adjust the filters and try again.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-cine-border/10 px-4 py-3">{{ $rows->links() }}</div>
    </div>
</x-admin.layout>
