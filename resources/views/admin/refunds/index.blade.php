<x-admin.layout title="Refunds" breadcrumb="CineBook / Admin / Refunds">
    <x-admin.page-header
        title="Refunds"
        subtitle="Review refund requests and processing status across bookings."
    >
        <x-slot:actions>
            <x-admin.filter-dropdown
                :action="route('admin.refunds.index')"
                :label="\Illuminate\Support\Carbon::parse($filters['from'])->format('M j, Y').' — '.\Illuminate\Support\Carbon::parse($filters['to'])->format('M j, Y')"
            >
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <p class="mb-3 text-xs font-semibold text-cine-primary">Choose date range</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label>
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">From</span>
                        <input name="from" type="date" value="{{ $filters['from'] }}" class="min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    </label>
                    <label>
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">To</span>
                        <input name="to" type="date" value="{{ $filters['to'] }}" class="min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <a href="{{ route('admin.refunds.index') }}" class="inline-flex min-h-9 items-center rounded-lg border border-cine-border/10 px-3 text-xs font-semibold text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">Reset</a>
                    <x-admin.button type="submit" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs">Apply filters</x-admin.button>
                </div>
            </x-admin.filter-dropdown>
            <a href="{{ route('admin.refunds.export', request()->except('page')) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-cine-accent px-4 text-xs font-semibold text-white shadow-cine-glow transition hover:brightness-110">
                <x-admin.icon name="download" class="h-4 w-4" />
                Export CSV
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @php
        $summaryCards = [
            ['label' => 'Total refunds', 'value' => number_format($totals['count']), 'detail' => 'Requests in selected date range', 'icon' => 'undo-2', 'tone' => 'text-cine-accent', 'iconTone' => 'bg-cine-accent/10'],
            ['label' => 'Refunded amount', 'cents' => $totals['amount_cents'], 'detail' => 'Successfully returned to customers', 'icon' => 'dollar-sign', 'tone' => 'text-cine-success', 'iconTone' => 'bg-cine-success/10'],
            ['label' => 'Pending refunds', 'value' => number_format($totals['pending']), 'detail' => 'Awaiting provider confirmation', 'icon' => 'clock', 'tone' => 'text-cine-warning', 'iconTone' => 'bg-cine-warning/10'],
            ['label' => 'Failed refunds', 'value' => number_format($totals['failed']), 'detail' => 'Need attention', 'icon' => 'alert-triangle', 'tone' => 'text-cine-danger', 'iconTone' => 'bg-cine-danger/10'],
        ];
        $reasonLabels = [
            'requested_by_customer' => 'Customer request',
            'duplicate' => 'Duplicate payment',
            'fraudulent' => 'Fraudulent payment',
        ];
    @endphp

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <div class="min-h-[112px] rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg p-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $card['iconTone'] }} {{ $card['tone'] }}">
                        <x-admin.icon :name="$card['icon']" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-cine-secondary">{{ $card['label'] }}</p>
                        <p class="mt-0.5 text-2xl font-bold tracking-tight text-cine-primary">
                            @if (isset($card['cents']))
                                <x-admin.money :cents="$card['cents']" />
                            @else
                                {{ $card['value'] }}
                            @endif
                        </p>
                    </div>
                </div>
                <p class="mt-3 min-h-4 text-[11px] text-cine-tertiary">{{ $card['detail'] }}</p>
            </div>
        @endforeach
    </div>

    <x-admin.card class="!p-0">
        <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h3 class="text-sm font-bold text-cine-primary">Recent refunds</h3>
                <p class="mt-1 text-[10px] text-cine-tertiary">Refund activity from {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('M j, Y') }} to {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('M j, Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] text-cine-tertiary">{{ $refunds->total() }} matching</span>
                <form method="GET" action="{{ route('admin.refunds.index') }}" class="relative w-full sm:w-80">
                    <input type="hidden" name="from" value="{{ $filters['from'] }}">
                    <input type="hidden" name="to" value="{{ $filters['to'] }}">
                    <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-tertiary" />
                    <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search refunds..." aria-label="Search refunds" class="min-h-9 w-full rounded-lg border border-cine-border/15 bg-cine-bg pl-9 pr-3 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                </form>
            </div>
        </div>

        @if ($refunds->isEmpty())
            <div class="p-8">
                <x-admin.empty-state icon="undo-2" title="No refunds found" text="Try changing the date range or search." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="admin-data-table w-full min-w-[780px] table-fixed divide-y divide-cine-border/10">
                    <colgroup>
                        <col class="w-[20%]">
                        <col class="w-[22%]">
                        <col class="w-[14%]">
                        <col class="w-[15%]">
                        <col class="w-[17%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead class="bg-cine-elevated/40">
                        <tr class="text-left text-[9px] font-bold uppercase tracking-wider text-cine-tertiary">
                            <th scope="col" class="px-3 py-3">Refund / booking</th>
                            <th scope="col" class="px-3 py-3">Customer</th>
                            <th scope="col" class="px-3 py-3">Amount</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="px-3 py-3">Reason</th>
                            <th scope="col" class="px-3 py-3">Requested</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($refunds as $refund)
                            @php
                                $statusStyle = match ($refund->status->value) {
                                    'succeeded' => 'success',
                                    'failed' => 'danger',
                                    default => 'warning',
                                };
                            @endphp
                            <tr class="transition hover:bg-cine-elevated/30">
                                <td class="whitespace-nowrap px-3 py-3">
                                    <a href="{{ route('admin.bookings.show', $refund->booking) }}" class="group inline-flex max-w-full flex-col rounded-md text-left">
                                        <span class="truncate text-xs font-semibold text-cine-accent transition group-hover:text-cine-primary">RF-{{ str_pad((string) $refund->getKey(), 5, '0', STR_PAD_LEFT) }}</span>
                                        <span class="mt-1 truncate text-[10px] text-cine-tertiary group-hover:text-cine-secondary">{{ $refund->booking->reference }}</span>
                                    </a>
                                </td>
                                <td class="min-w-0 px-3 py-3">
                                    <span class="block truncate text-xs text-cine-secondary">{{ $refund->booking->user->name }}</span>
                                    <span class="mt-1 block truncate text-[10px] text-cine-tertiary">{{ $refund->booking->user->email }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs font-semibold text-cine-primary">
                                    <x-admin.money :cents="$refund->amount_cents" :currency="$refund->currency" />
                                </td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <x-admin.badge :status="$statusStyle">
                                        {{ ucfirst($refund->status->value) }}
                                    </x-admin.badge>
                                    @if ($refund->failure_message)
                                        <p class="mt-1 max-w-40 truncate text-[10px] text-cine-tertiary" title="{{ $refund->failure_message }}">{{ $refund->failure_message }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="block truncate text-xs text-cine-secondary">{{ $reasonLabels[$refund->reason] ?? str($refund->reason)->replace('_', ' ')->title() }}</span>
                                    @if ($refund->gateway_refund_id)
                                        <span class="mt-1 block truncate font-mono text-[9px] text-cine-tertiary">{{ $refund->gateway_refund_id }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs text-cine-secondary">
                                    {{ $refund->created_at->format('M j · H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[11px] text-cine-tertiary">
                    Showing {{ $refunds->firstItem() }}–{{ $refunds->lastItem() }} of {{ number_format($refunds->total()) }} refunds
                </p>
                <div>{{ $refunds->onEachSide(1)->links('vendor.pagination.admin') }}</div>
            </div>
        @endif
    </x-admin.card>

</x-admin.layout>
