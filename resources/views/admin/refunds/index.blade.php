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
            ['label' => 'Total refunds', 'value' => number_format($totals['count']), 'trend' => $trends['count'], 'trendKind' => 'volume', 'icon' => 'undo-2', 'tone' => 'text-cine-accent', 'iconTone' => 'bg-cine-accent/10'],
            ['label' => 'Refunded amount', 'cents' => $totals['amount_cents'], 'trend' => $trends['amount'], 'trendKind' => 'volume', 'icon' => 'dollar-sign', 'tone' => 'text-cine-success', 'iconTone' => 'bg-cine-success/10'],
            ['label' => 'Pending refunds', 'value' => number_format($totals['pending']), 'trend' => $trends['pending'], 'trendKind' => 'risk', 'icon' => 'clock', 'tone' => 'text-cine-warning', 'iconTone' => 'bg-cine-warning/10'],
            ['label' => 'Failed refunds', 'value' => number_format($totals['failed']), 'trend' => $trends['failed'], 'trendKind' => 'failure', 'icon' => 'alert-triangle', 'tone' => 'text-cine-danger', 'iconTone' => 'bg-cine-danger/10'],
        ];
        $trendTone = fn (string $direction, string $kind): string => match (true) {
            $direction === 'neutral' => 'text-cine-tertiary',
            $direction === 'up' && $kind === 'risk' => 'text-cine-warning',
            $direction === 'up' && $kind === 'failure' => 'text-cine-danger',
            $direction === 'up' => 'text-cine-success',
            $kind !== 'volume' => 'text-cine-success',
            $direction === 'down' => 'text-cine-danger',
            default => 'text-cine-tertiary',
        };
        $reasonLabels = [
            'requested_by_customer' => 'Customer request',
            'duplicate' => 'Duplicate payment',
            'fraudulent' => 'Fraudulent payment',
        ];
    @endphp

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <div class="rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg p-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $card['iconTone'] }} {{ $card['tone'] }}">
                        <x-admin.icon :name="$card['icon']" class="h-6 w-6" />
                    </span>
                    <p class="text-xs font-medium text-cine-secondary">{{ $card['label'] }}</p>
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight text-cine-primary">
                    @if (isset($card['cents']))
                        <x-admin.money :cents="$card['cents']" />
                    @else
                        {{ $card['value'] }}
                    @endif
                </p>
                <p class="mt-3 flex min-h-4 items-center gap-2 text-[11px] {{ $trendTone($card['trend']['direction'], $card['trendKind']) }}">
                    <span class="font-bold">{{ $card['trend']['direction'] === 'up' ? '↑' : ($card['trend']['direction'] === 'down' ? '↓' : '·') }} {{ $card['trend']['label'] }}</span>
                    <span class="text-cine-tertiary">vs. previous period</span>
                </p>
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
                <table class="w-full min-w-[780px] table-fixed divide-y divide-cine-border/10">
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
                                    'succeeded' => 'bg-cine-success/10 text-cine-success',
                                    'failed' => 'bg-cine-danger/10 text-cine-danger',
                                    default => 'bg-cine-warning/10 text-cine-warning',
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
                                    <span @class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold', $statusStyle])>
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ ucfirst($refund->status->value) }}
                                    </span>
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

    <div class="mt-4 grid items-stretch gap-4 xl:grid-cols-2">
        <x-admin.card class="h-full">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cine-accent/10 text-cine-accent">
                    <x-admin.icon name="undo-2" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-cine-accent">Refund confirmation</p>
                    @if ($latestFailedRefund)
                        <h3 class="mt-1 text-base font-bold text-cine-primary">
                            Review RF-{{ str_pad((string) $latestFailedRefund->getKey(), 5, '0', STR_PAD_LEFT) }}
                        </h3>
                        <p class="mt-2 text-xs leading-5 text-cine-secondary">
                            {{ $latestFailedRefund->failure_message ?: 'The provider could not complete this refund. Check the booking and payment before taking further action.' }}
                        </p>
                        <div class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-cine-tertiary">
                            <span>Booking</span>
                            <span class="font-medium text-cine-secondary">{{ $latestFailedRefund->booking->reference }}</span>
                            <span aria-hidden="true">·</span>
                            <span>Amount</span>
                            <span class="font-semibold text-cine-primary"><x-admin.money :cents="$latestFailedRefund->amount_cents" :currency="$latestFailedRefund->currency" /></span>
                        </div>
                        @if ($latestFailedRefund->payment->gateway === 'stripe')
                            <a href="{{ route('admin.bookings.show', $latestFailedRefund->booking) }}" class="mt-4 inline-flex min-h-9 items-center gap-2 rounded-lg border border-cine-border/10 bg-cine-elevated px-3 text-xs font-semibold text-cine-primary transition hover:border-cine-accent/30">
                                Review booking and retry options
                                <x-admin.icon name="arrow-right" class="h-3.5 w-3.5" />
                            </a>
                        @else
                            <p class="mt-4 rounded-lg border border-cine-warning/15 bg-cine-warning/5 px-3 py-2 text-[10px] leading-4 text-cine-warning">
                                This is demo payment data. It cannot be retried through Stripe.
                            </p>
                        @endif
                    @else
                        <h3 class="mt-1 text-base font-bold text-cine-primary">No failed refunds to review</h3>
                        <p class="mt-2 text-xs leading-5 text-cine-secondary">Failed refund requests will appear here with their booking and amount for review.</p>
                    @endif
                </div>
            </div>
        </x-admin.card>

        <x-admin.card class="h-full">
            <div class="flex items-center gap-2">
                <x-admin.icon name="clock" class="h-4 w-4 text-cine-accent" />
                <h3 class="text-sm font-bold text-cine-primary">Refund lifecycle</h3>
            </div>
            <div class="relative mt-6 grid grid-cols-3 gap-2">
                <div class="absolute left-[16%] right-[16%] top-3 h-px bg-cine-border/15"></div>
                @foreach ([
                    ['number' => '1', 'title' => 'Pending', 'detail' => 'Refund request received'],
                    ['number' => '2', 'title' => 'Processing', 'detail' => 'Provider is handling the refund'],
                    ['number' => '3', 'title' => 'Completed', 'detail' => 'Refund sent to customer'],
                ] as $step)
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <span @class([
                            'flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold',
                            'bg-cine-warning text-cine-bg' => $step['number'] === '1' && $totals['pending'] > 0,
                            'bg-cine-success text-cine-bg' => $step['number'] === '3' && $totals['succeeded'] > 0,
                            'bg-cine-elevated text-cine-secondary' => ! ($step['number'] === '1' && $totals['pending'] > 0) && ! ($step['number'] === '3' && $totals['succeeded'] > 0),
                        ])>{{ $step['number'] }}</span>
                        <p class="mt-2 text-[10px] font-semibold text-cine-primary">{{ $step['title'] }}</p>
                        <p class="mt-1 max-w-28 text-[9px] leading-4 text-cine-tertiary">{{ $step['detail'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 flex items-start gap-2 rounded-xl border border-cine-warning/15 bg-cine-warning/5 px-3 py-2.5">
                <x-admin.icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-cine-warning" />
                <p class="text-[10px] leading-4 text-cine-secondary">
                    {{ number_format($totals['pending']) }} refund{{ $totals['pending'] === 1 ? '' : 's' }} pending in this date range. The booking remains cancelled while the provider confirms the refund.
                </p>
            </div>
        </x-admin.card>
    </div>
</x-admin.layout>
