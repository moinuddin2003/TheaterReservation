<x-admin.layout :title="$staff->name" :breadcrumb="'CineBook / People / Staff & admins / '.$staff->name">
    <x-admin.page-header
        :title="$staff->name"
        :back-url="route('admin.staff-admins.index')"
        back-label="Back to staff & admins"
    >
        <x-slot:meta>
            <div class="flex flex-wrap items-center gap-2">
                <x-admin.badge status="success">Staff</x-admin.badge>
                <span class="text-[11px] text-cine-tertiary">Member since {{ $staff->created_at->format('M Y') }}</span>
            </div>
        </x-slot:meta>
    </x-admin.page-header>

    <div class="grid items-start gap-4 xl:grid-cols-[minmax(300px,0.95fr)_minmax(0,1.65fr)]">
        <div class="space-y-3 self-start xl:sticky xl:top-24">
            <x-admin.card class="!p-4 sm:!p-5">
                <div class="flex items-center gap-3 border-b border-cine-border/10 pb-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-cine-success/20 bg-cine-success/10 text-cine-success">
                        <x-admin.icon name="users" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-bold text-cine-primary">{{ $staff->name }}</h3>
                        <p class="mt-1 truncate text-[10px] text-cine-tertiary">{{ $staff->email }}</p>
                    </div>
                </div>
                <dl class="divide-y divide-cine-border/10">
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-[10px] text-cine-tertiary">Member since</dt>
                        <dd class="text-right text-[10px] font-semibold text-cine-primary">{{ $staff->created_at->format('M j, Y') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-[10px] text-cine-tertiary">Role</dt>
                        <dd class="text-right text-[10px] font-semibold text-cine-primary">Staff</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 pt-2.5">
                        <dt class="text-[10px] text-cine-tertiary">Last scan</dt>
                        <dd class="text-right text-[10px] font-semibold text-cine-primary">
                            {{ $lastScan ? \Illuminate\Support\Carbon::parse($lastScan)->timezone(config('app.timezone'))->format('M j · g:i A') : 'No scans yet' }}
                        </dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card class="!p-4 sm:!p-5">
                <h3 class="text-sm font-bold text-cine-primary">Scan summary</h3>
                <dl class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-cine-border/10 bg-cine-bg p-3">
                        <dt class="text-[10px] text-cine-tertiary">Total scans</dt>
                        <dd class="mt-1 text-base font-bold text-cine-primary">{{ number_format($staff->ticket_scans_count) }}</dd>
                    </div>
                    <div class="rounded-xl border border-cine-border/10 bg-cine-bg p-3">
                        <dt class="text-[10px] text-cine-tertiary">Admitted</dt>
                        <dd class="mt-1 text-base font-bold text-cine-success">{{ number_format($admittedScans) }}</dd>
                    </div>
                </dl>
            </x-admin.card>

            <div class="flex items-start gap-2 rounded-xl border border-cine-accent/20 bg-cine-accent/10 px-3 py-2.5">
                <x-admin.icon name="lock" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-cine-accent" />
                <p class="text-[10px] leading-4 text-cine-secondary">Staff account details are read-only for admins. Scan records are retained for the audit trail.</p>
            </div>
        </div>

        <x-admin.card class="!p-0">
            <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-4">
                <h3 class="text-sm font-bold text-cine-primary">
                    Scan history
                    <span class="ml-1 rounded-full bg-cine-elevated px-2 py-0.5 text-[10px] font-semibold text-cine-secondary">{{ number_format($scans->total()) }}</span>
                </h3>
                <form method="GET" action="{{ route('admin.staff-admins.show', $staff) }}" class="flex flex-wrap gap-2">
                    <label class="sr-only" for="scan-result">Scan result</label>
                    <select id="scan-result" name="result" x-on:change="$el.form.requestSubmit()" class="min-h-8 rounded-lg border border-cine-border/15 bg-cine-bg px-2.5 text-[10px] text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                        <option value="">All results</option>
                        @foreach (\App\Enums\ScanResult::cases() as $scanResult)
                            <option value="{{ $scanResult->value }}" @selected($filters['result'] === $scanResult->value)>
                                {{ str($scanResult->value)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="scan-sort">Sort scans</label>
                    <select id="scan-sort" name="sort" x-on:change="$el.form.requestSubmit()" class="min-h-8 rounded-lg border border-cine-border/15 bg-cine-bg px-2.5 text-[10px] text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                        <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                    </select>
                </form>
            </div>

            @if ($scans->isEmpty())
                <div class="p-8">
                    <x-admin.empty-state icon="scan-line" title="No scans found" text="This staff member has no scans matching this filter." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="admin-data-table w-full min-w-[790px] table-fixed divide-y divide-cine-border/10 xl:min-w-full">
                        <colgroup>
                            <col class="w-[18%]">
                            <col class="w-[22%]">
                            <col class="w-[21%]">
                            <col class="w-[19%]">
                            <col class="w-[20%]">
                        </colgroup>
                        <thead class="bg-cine-elevated/40">
                            <tr class="text-left text-[9px] font-bold uppercase tracking-wider text-cine-tertiary">
                                <th scope="col" class="px-2.5 py-3">Ticket</th>
                                <th scope="col" class="px-2.5 py-3">Movie / customer</th>
                                <th scope="col" class="px-2.5 py-3">Showtime</th>
                                <th scope="col" class="px-2.5 py-3">Result</th>
                                <th scope="col" class="px-2.5 py-3">Scanned</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cine-border/10">
                            @foreach ($scans as $scan)
                                @php
                                    $resultStyle = match ($scan->result->value) {
                                        'admitted' => 'success',
                                        'invalid', 'void' => 'danger',
                                        default => 'warning',
                                    };
                                @endphp
                                <tr class="transition hover:bg-cine-elevated/30">
                                    <td class="px-2.5 py-2.5">
                                        <span class="block truncate text-[10px] font-semibold text-cine-primary">{{ $scan->ticket?->ticket_number ?? 'Unknown ticket' }}</span>
                                        <span class="mt-1 block truncate text-[9px] text-cine-tertiary">{{ $scan->ticket?->booking?->reference ?? 'Unmatched scan' }}</span>
                                    </td>
                                    <td class="px-2.5 py-2.5">
                                        <span class="block truncate text-[10px] font-semibold text-cine-primary">{{ $scan->showtime?->movie?->title ?? 'Unknown movie' }}</span>
                                        <span class="mt-1 block truncate text-[9px] text-cine-tertiary">{{ $scan->ticket?->booking?->user?->name ?? 'Unknown customer' }}</span>
                                    </td>
                                    <td class="px-2.5 py-2.5">
                                        @if ($scan->showtime)
                                            <span class="block truncate text-[10px] font-semibold text-cine-primary">{{ $scan->showtime->starts_at->format('D j M Y') }}</span>
                                            <span class="mt-1 block truncate text-[9px] text-cine-tertiary">{{ $scan->showtime->starts_at->format('H:i') }} · {{ $scan->showtime->screen->name }}</span>
                                        @else
                                            <span class="text-[9px] text-cine-tertiary">No showtime</span>
                                        @endif
                                    </td>
                                    <td class="px-2.5 py-2.5">
                                        <x-admin.badge :status="$resultStyle">
                                            <span class="text-[9px]">{{ str($scan->result->value)->replace('_', ' ')->title() }}</span>
                                        </x-admin.badge>
                                        @if ($scan->message)
                                            <span class="mt-1 block max-w-36 truncate text-[9px] text-cine-tertiary" title="{{ $scan->message }}">{{ $scan->message }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-2.5 py-2.5 text-[10px] text-cine-secondary">
                                        {{ $scan->scanned_at->timezone(config('app.timezone'))->format('M j · g:i A') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[11px] text-cine-tertiary">
                        Showing {{ $scans->firstItem() }}–{{ $scans->lastItem() }} of {{ number_format($scans->total()) }} scans
                    </p>
                    <div>{{ $scans->onEachSide(1)->links('vendor.pagination.admin') }}</div>
                </div>
            @endif
        </x-admin.card>
    </div>
</x-admin.layout>
