<x-admin.layout title="Customers" breadcrumb="CineBook / People / Customers">
    <x-admin.page-header
        title="Customers"
        subtitle="Review customer accounts and their booking activity."
    >
        <x-slot:actions>
            <x-admin.filter-dropdown :action="route('admin.customers.index')" label="Filters">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <label>
                    <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Customer status</span>
                    <select name="status" class="min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                        <option value="">All customers</option>
                        <option value="verified" @selected($filters['status'] === 'verified')>Verified</option>
                        <option value="unverified" @selected($filters['status'] === 'unverified')>Unverified</option>
                    </select>
                </label>
                <div class="mt-4 flex justify-end gap-2">
                    <a href="{{ route('admin.customers.index') }}" class="inline-flex min-h-9 items-center rounded-lg border border-cine-border/10 px-3 text-xs font-semibold text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">Reset</a>
                    <x-admin.button type="submit" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs">Apply filters</x-admin.button>
                </div>
            </x-admin.filter-dropdown>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card class="!p-0">
        <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h3 class="text-sm font-bold text-cine-primary">Customer accounts</h3>
                <p class="mt-1 text-[10px] text-cine-tertiary">Customer activity and booking history</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="whitespace-nowrap text-[10px] text-cine-tertiary">{{ number_format($customers->total()) }} matching</span>
                <form method="GET" action="{{ route('admin.customers.index') }}" class="relative w-full sm:w-80">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-tertiary" />
                    <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search customers..." aria-label="Search customers" class="min-h-9 w-full rounded-lg border border-cine-border/15 bg-cine-bg pl-9 pr-3 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                </form>
            </div>
        </div>

        @if ($customers->isEmpty())
            <div class="p-8">
                <x-admin.empty-state icon="users" title="No customers found" text="Try changing your search or status filter." />
            </div>
        @else
            <div class="overflow-x-auto px-2 py-1">
                <table class="admin-data-table w-full min-w-[820px] table-fixed divide-y divide-cine-border/10">
                    <colgroup>
                        <col class="w-[7%]">
                        <col class="w-[24%]">
                        <col class="w-[30%]">
                        <col class="w-[15%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead class="bg-cine-elevated/40">
                        <tr class="text-left text-[9px] font-bold uppercase tracking-wider text-cine-tertiary">
                            <th scope="col" class="px-2 py-3 sm:px-3">#</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Name</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Email</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Status</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Bookings</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Total spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($customers as $customer)
                            <tr class="transition hover:bg-cine-elevated/30">
                                <td class="whitespace-nowrap px-2 py-3.5 text-xs text-cine-secondary sm:px-3">
                                    {{ $customers->firstItem() + $loop->index }}
                                </td>
                                <td class="px-2 py-3.5 sm:px-3">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="block truncate text-xs text-cine-secondary transition hover:text-cine-primary">
                                        {{ $customer->name }}
                                    </a>
                                </td>
                                <td class="truncate px-2 py-3.5 text-xs text-cine-secondary sm:px-3">
                                    {{ $customer->email }}
                                </td>
                                <td class="whitespace-nowrap px-2 py-3.5 sm:px-3">
                                    @if ($customer->email_verified_at)
                                        <x-admin.badge status="success">Verified</x-admin.badge>
                                    @else
                                        <x-admin.badge status="warning">Unverified</x-admin.badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-2 py-3.5 text-xs text-cine-primary sm:px-3">
                                    {{ number_format($customer->bookings_count) }}
                                </td>
                                <td class="whitespace-nowrap px-2 py-3.5 text-xs font-medium text-cine-primary sm:px-3">
                                    <x-admin.money :cents="(int) $customer->total_spent_cents" currency="USD" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[11px] text-cine-tertiary">
                    Showing {{ $customers->firstItem() }}–{{ $customers->lastItem() }} of {{ number_format($customers->total()) }} customers
                </p>
                <div>{{ $customers->onEachSide(1)->links('vendor.pagination.admin') }}</div>
            </div>
        @endif
    </x-admin.card>

    <div class="mt-4 flex items-start gap-3 rounded-xl border border-cine-accent/20 bg-cine-accent/10 px-4 py-3">
        <x-admin.icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-cine-accent" />
        <div>
            <p class="text-xs font-semibold text-cine-primary">Customer records are retained</p>
            <p class="mt-1 text-xs leading-5 text-cine-secondary">Customer deletion is not available. Select a customer name to open the details section.</p>
        </div>
    </div>
</x-admin.layout>
