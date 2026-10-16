@props(['rows', 'columns', 'emptyTitle' => 'No records found', 'emptyText' => 'Try changing your search or filters.'])

<div class="overflow-hidden rounded-2xl border border-cine-border/10 bg-cine-surface shadow-cine-card">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-cine-border/10 text-left text-sm">
            <thead class="sticky top-0 z-10 bg-cine-elevated/90 text-xs uppercase tracking-wide text-cine-tertiary backdrop-blur">
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">{{ $column['label'] }}</th>
                    @endforeach
                    @if (isset($actions))
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-cine-border/10">
                @forelse ($rows as $row)
                    <tr class="transition-colors hover:bg-cine-elevated/30">
                        @foreach ($columns as $column)
                            <td class="whitespace-nowrap px-4 py-3.5 text-cine-primary">
                                @if (isset($column['slot']))
                                    {{ $column['slot']($row) }}
                                @elseif (($column['type'] ?? null) === 'link')
                                    <a class="font-semibold text-cine-accent hover:text-cine-primary" href="{{ route($column['route'], $row) }}">{{ $column['link_label'] ?? 'Details' }}</a>
                                @elseif (($column['type'] ?? null) === 'relation-link')
                                    @if (data_get($row, $column['relation']))
                                        <a class="font-semibold text-cine-accent hover:text-cine-primary" href="{{ route($column['route'], data_get($row, $column['relation'])) }}">{{ data_get($row, $column['label_key'] ?? $column['relation'].'.ticket_number') }}</a>
                                    @else
                                        —
                                    @endif
                                @else
                                    {{ data_get($row, $column['key']) ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                        @if (isset($actions))
                            <td class="px-4 py-3 text-right">{{ $actions($row) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + (isset($actions) ? 1 : 0) }}" class="px-5 py-12">
                            <x-admin.empty-state icon="search" :title="$emptyTitle" :text="$emptyText" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (method_exists($rows, 'links'))
        <div class="border-t border-cine-border/10 px-4 py-3">{{ $rows->withQueryString()->links() }}</div>
    @endif
</div>
