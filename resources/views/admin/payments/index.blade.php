<x-admin.layout title="Payments" breadcrumb="CineBook / Admin / Payments">
    <x-admin.page-header
        title="Payments"
        subtitle="View and manage payment records for all bookings."
    >
        <x-slot:actions>
            <x-admin.filter-dropdown :action="route('admin.payments.index')" label="filters" width="w-[min(92vw,560px)]">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label>
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">From</span>
                        <input name="from" type="date" value="{{ $filters['from'] }}" class="min-h-10 w-full rounded-xl border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    </label>
                    <label>
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">To</span>
                        <input name="to" type="date" value="{{ $filters['to'] }}" class="min-h-10 w-full rounded-xl border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                    </label>
                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Payment state</span>
                        <select name="status" class="min-h-10 w-full rounded-xl border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                            <option value="">All states</option>
                            <option value="succeeded" @selected($filters['status'] === 'succeeded')>Succeeded</option>
                            <option value="pending" @selected($filters['status'] === 'pending')>Processing</option>
                            <option value="failed" @selected($filters['status'] === 'failed')>Failed</option>
                            <option value="expired" @selected($filters['status'] === 'expired')>Expired</option>
                            <option value="refunded" @selected($filters['status'] === 'refunded')>Refunded</option>
                        </select>
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <a href="{{ route('admin.payments.index') }}" class="inline-flex min-h-9 items-center rounded-lg border border-cine-border/10 px-3 text-xs font-semibold text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">Reset</a>
                    <x-admin.button type="submit" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs">Apply filters</x-admin.button>
                </div>
            </x-admin.filter-dropdown>
            <a href="{{ route('admin.payments.export', request()->except('page')) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-cine-accent px-4 text-xs font-semibold text-white shadow-cine-glow transition hover:brightness-110">
                <x-admin.icon name="download" class="h-4 w-4" />
                Export CSV
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 flex items-start gap-3 rounded-xl border border-cine-accent/20 bg-cine-accent/10 px-4 py-3">
        <x-admin.icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-cine-accent" />
        <div>
            <p class="text-xs font-semibold text-cine-primary">Real-time payment data</p>
            <p class="mt-1 text-xs leading-5 text-cine-secondary">
                Successful payments are confirmed by verified Stripe events. Payment state and card details cannot be manually edited here.
                @if (config('services.stripe.secret') && str_starts_with(config('services.stripe.secret'), 'sk_test_'))
                    <span class="font-semibold text-cine-warning">Stripe TEST MODE</span>
                @endif
            </p>
        </div>
    </div>

    <x-admin.card class="!p-0">
        <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h3 class="text-sm font-bold text-cine-primary">Recent payments</h3>
                <p class="mt-1 text-[10px] text-cine-tertiary">Payment activity across all bookings</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] text-cine-tertiary">{{ number_format($payments->total()) }} matching</span>
                <form method="GET" action="{{ route('admin.payments.index') }}" class="relative w-full sm:w-80">
                <input type="hidden" name="from" value="{{ $filters['from'] }}">
                <input type="hidden" name="to" value="{{ $filters['to'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-tertiary" />
                <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search payments..." aria-label="Search payments" class="min-h-9 w-full rounded-lg border border-cine-border/15 bg-cine-bg pl-9 pr-3 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                </form>
            </div>
        </div>
        @if ($payments->isEmpty())
            <div class="p-8">
                <x-admin.empty-state icon="credit-card" title="No payments found" text="Try changing your search or filters." />
            </div>
        @else
            <div class="overflow-x-auto px-2 py-1">
                <table class="w-full min-w-[1040px] table-fixed divide-y divide-cine-border/10">
                    <colgroup>
                        <col class="w-[15%]">
                        <col class="w-[21%]">
                        <col class="w-[20%]">
                        <col class="w-[14%]">
                        <col class="w-[13%]">
                        <col class="w-[7%]">
                        <col class="w-[10%]">
                    </colgroup>
                    <thead class="bg-cine-elevated/40">
                        <tr class="text-left text-[9px] font-bold uppercase tracking-wider text-cine-tertiary">
                            <th scope="col" class="px-2 py-3 sm:px-3">Payment / booking</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Customer</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Movie / showtime</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Amount</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Status</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Gateway</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($payments as $payment)
                            @php
                                $statusStyle = match ($payment->status->value) {
                                    'succeeded' => 'bg-cine-success/10 text-cine-success',
                                    'failed', 'expired' => 'bg-cine-danger/10 text-cine-danger',
                                    'refunded' => 'bg-cine-warning/10 text-cine-warning',
                                    default => 'bg-cine-accent/10 text-cine-accent',
                                };
                            @endphp
                            <tr class="transition hover:bg-cine-elevated/30">
                                <td class="whitespace-nowrap px-2 py-3.5 sm:px-3">
                                    <a href="{{ route('admin.bookings.show', $payment->booking) }}" class="group inline-flex max-w-full flex-col rounded-md text-left">
                                        <span class="truncate text-xs font-semibold text-cine-accent transition group-hover:text-cine-primary">PAY-{{ str_pad((string) $payment->getKey(), 5, '0', STR_PAD_LEFT) }}</span>
                                        <span class="mt-1 truncate text-[10px] text-cine-tertiary transition group-hover:text-cine-secondary">{{ $payment->booking->reference }}</span>
                                    </a>
                                </td>
                                <td class="min-w-0 px-2 py-3.5 sm:px-3">
                                    <span class="block truncate text-xs text-cine-secondary">{{ $payment->booking->user->name }}</span>
                                    <span class="mt-1 block truncate text-[10px] text-cine-tertiary">{{ $payment->booking->user->email }}</span>
                                </td>
                                <td class="min-w-0 px-2 py-3.5 sm:px-3">
                                    <span class="block truncate text-xs text-cine-secondary">{{ $payment->booking->showtime->movie->title }}</span>
                                    <span class="mt-1 block truncate text-[10px] text-cine-tertiary">
                                        {{ $payment->booking->showtime->starts_at->format('M j · H:i') }} · {{ $payment->booking->showtime->screen->name }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-2 py-3.5 text-xs font-medium text-cine-primary sm:px-3">
                                    <x-admin.money :cents="$payment->amount_cents" :currency="$payment->currency" />
                                </td>
                                <td class="whitespace-nowrap px-2 py-3.5 sm:px-3">
                                    <span @class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold', $statusStyle])>
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ ucfirst($payment->status->value === 'pending' ? 'Processing' : $payment->status->value) }}
                                    </span>
                                    @if ($payment->failure_reason)
                                        <p class="mt-1 max-w-48 truncate text-[10px] text-cine-tertiary" title="{{ $payment->failure_reason }}">{{ $payment->failure_reason }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-2 py-3.5 text-xs text-cine-secondary sm:px-3">
                                    {{ str($payment->gateway)->title() }}
                                </td>
                                <td class="whitespace-nowrap px-2 py-3.5 text-xs text-cine-secondary sm:px-3">
                                    {{ ($payment->paid_at ?? $payment->updated_at)->format('M j · H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[11px] text-cine-tertiary">
                    Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ number_format($payments->total()) }} payments
                </p>
                <div>{{ $payments->onEachSide(1)->links('vendor.pagination.admin') }}</div>
            </div>
        @endif
    </x-admin.card>
</x-admin.layout>
