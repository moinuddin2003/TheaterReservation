<x-admin.layout title="Tickets & Scans" breadcrumb="CineBook / Admin / Tickets & Scans">
    <x-admin.page-header
        title="Tickets & Scans"
        subtitle="Track issued tickets and review entry scan activity."
    >
        <x-slot:actions>
            <x-admin.filter-dropdown
                :action="route('admin.tickets.index')"
                :label="$filters['from'] || $filters['to'] ? 'Date range' : 'Filters'"
            >
                <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <p class="mb-3 text-xs font-semibold text-cine-primary">Filter {{ $filters['tab'] === 'tickets' ? 'tickets' : 'scan log' }}</p>
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Movie</span>
                        <select name="movie_id" class="min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                            <option value="">All movies</option>
                            @foreach ($movies as $movie)
                                <option value="{{ $movie->id }}" @selected($filters['movie_id'] === $movie->id)>{{ $movie->title }}</option>
                            @endforeach
                        </select>
                    </label>
                    @if ($filters['tab'] === 'tickets')
                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Ticket status</span>
                            <select name="status" class="min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                                <option value="">All statuses</option>
                                @foreach (\App\Enums\TicketStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ str_replace('_', ' ', ucfirst($status->value)) }}</option>
                                @endforeach
                            </select>
                        </label>
                    @else
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block">
                                <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Result</span>
                                <select name="result" class="min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                                    <option value="">All results</option>
                                    @foreach (\App\Enums\ScanResult::cases() as $result)
                                        <option value="{{ $result->value }}" @selected($filters['result'] === $result->value)>{{ str_replace('_', ' ', ucfirst($result->value)) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Staff</span>
                                <select name="staff_id" class="min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                                    <option value="">All staff</option>
                                    @foreach ($staff as $member)
                                        <option value="{{ $member->id }}" @selected($filters['staff_id'] === $member->id)>{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    @endif
                    <p class="pt-1 text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Date range</p>
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
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <a href="{{ route('admin.tickets.index', ['tab' => $filters['tab']]) }}" class="inline-flex min-h-9 items-center rounded-lg border border-cine-border/10 px-3 text-xs font-semibold text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">Reset</a>
                    <x-admin.button type="submit" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs">Apply filters</x-admin.button>
                </div>
            </x-admin.filter-dropdown>
            <a href="{{ route('staff.scan') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-cine-accent px-4 text-xs font-semibold text-white shadow-cine-glow transition hover:brightness-110">
                <x-admin.icon name="scan-line" class="h-4 w-4" />
                Open scanner
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @php
        $cards = [
            ['label' => 'Tickets issued today', 'value' => $metrics['issued_today'], 'icon' => 'ticket', 'tone' => 'text-cine-accent', 'iconTone' => 'bg-cine-accent/10'],
            ['label' => 'Scanned today', 'value' => $metrics['scanned_today'], 'icon' => 'check-circle', 'tone' => 'text-cine-success', 'iconTone' => 'bg-cine-success/10'],
            ['label' => 'Duplicate attempts', 'value' => $metrics['duplicates_today'], 'icon' => 'rotate-ccw', 'tone' => 'text-cine-warning', 'iconTone' => 'bg-cine-warning/10'],
            ['label' => 'Voided tickets', 'value' => $metrics['voided'], 'icon' => 'ticket-x', 'tone' => 'text-cine-danger', 'iconTone' => 'bg-cine-danger/10'],
        ];
    @endphp

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <div class="min-h-[112px] rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg p-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $card['iconTone'] }} {{ $card['tone'] }}">
                        <x-admin.icon :name="$card['icon']" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-cine-secondary">{{ $card['label'] }}</p>
                        <p class="mt-0.5 text-2xl font-bold tracking-tight text-cine-primary">{{ number_format($card['value']) }}</p>
                    </div>
                </div>
                <p class="mt-3 min-h-4 text-[11px] text-cine-tertiary">Updated from recorded ticket activity</p>
            </div>
        @endforeach
    </div>

    <x-admin.card class="!p-0">
        <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 pb-3 pt-4 sm:px-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1" role="tablist" aria-label="Ticket records">
                    <a href="{{ route('admin.tickets.index', request()->except(['tab', 'tickets_page', 'scans_page', 'page']) + ['tab' => 'tickets']) }}" @class(['inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-xs font-semibold transition', 'bg-cine-accent/10 text-cine-accent' => $filters['tab'] === 'tickets', 'text-cine-tertiary hover:bg-cine-elevated hover:text-cine-primary' => $filters['tab'] !== 'tickets']) aria-current="{{ $filters['tab'] === 'tickets' ? 'page' : 'false' }}">
                        Tickets <span class="rounded-full bg-cine-bg px-2 py-0.5 text-[10px]">{{ number_format($counts['tickets']) }}</span>
                    </a>
                    <a href="{{ route('admin.tickets.index', request()->except(['tab', 'tickets_page', 'scans_page', 'page']) + ['tab' => 'scans']) }}" @class(['inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-xs font-semibold transition', 'bg-cine-accent/10 text-cine-accent' => $filters['tab'] === 'scans', 'text-cine-tertiary hover:bg-cine-elevated hover:text-cine-primary' => $filters['tab'] !== 'scans']) aria-current="{{ $filters['tab'] === 'scans' ? 'page' : 'false' }}">
                        Scan log <span class="rounded-full bg-cine-bg px-2 py-0.5 text-[10px]">{{ number_format($counts['scans']) }}</span>
                    </a>
                </div>
                <span class="hidden text-[10px] text-cine-tertiary sm:block">{{ $filters['tab'] === 'tickets' ? $tickets->total() : $scans->total() }} matching</span>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h3 class="text-sm font-bold text-cine-primary">{{ $filters['tab'] === 'tickets' ? 'Issued tickets' : 'Recent scan activity' }}</h3>
                <p class="mt-1 text-[10px] text-cine-tertiary">{{ $filters['tab'] === 'tickets' ? 'Ticket records linked to bookings and showtimes.' : 'Admission and rejected scan attempts recorded by staff.' }}</p>
            </div>
            <form method="GET" action="{{ route('admin.tickets.index') }}" class="relative w-full sm:w-80">
                <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                @foreach (['status', 'result', 'movie_id', 'staff_id', 'from', 'to'] as $filter)
                    @if ($filters[$filter] !== null)
                        <input type="hidden" name="{{ $filter }}" value="{{ $filters[$filter] }}">
                    @endif
                @endforeach
                <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-tertiary" />
                <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="{{ $filters['tab'] === 'tickets' ? 'Search tickets...' : 'Search scan log...' }}" aria-label="{{ $filters['tab'] === 'tickets' ? 'Search tickets' : 'Search scan log' }}" class="min-h-9 w-full rounded-lg border border-cine-border/15 bg-cine-bg pl-9 pr-3 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
            </form>
        </div>

        @if ($filters['tab'] === 'tickets')
            @if ($tickets->isEmpty())
                <div class="p-8">
                    <x-admin.empty-state icon="ticket" title="No tickets found" text="Try changing the filters or search terms." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="admin-data-table w-full min-w-[850px] table-fixed divide-y divide-cine-border/10">
                        <thead>
                            <tr>
                                <th class="w-[19%] px-4 py-3 text-left">Ticket</th>
                                <th class="w-[27%] px-4 py-3 text-left">Movie / Showtime</th>
                                <th class="w-[20%] px-4 py-3 text-left">Customer</th>
                                <th class="w-[10%] px-4 py-3 text-left">Seat</th>
                                <th class="w-[12%] px-4 py-3 text-left">Status</th>
                                <th class="w-[12%] px-4 py-3 text-left">Scans</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cine-border/10">
                            @foreach ($tickets as $ticket)
                                @php
                                    $ticketTone = match ($ticket->status->value) {
                                        'valid' => 'success',
                                        'used' => 'warning',
                                        default => 'danger',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.bookings.show', $ticket->booking) }}" class="font-semibold text-cine-primary transition hover:text-cine-accent">{{ $ticket->ticket_number }}</a>
                                        <p class="mt-1 text-[10px] text-cine-tertiary">{{ $ticket->booking->reference }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="truncate font-medium text-cine-primary">{{ $ticket->showtime->movie->title }}</p>
                                        <p class="mt-1 text-[10px] text-cine-tertiary">{{ $ticket->showtime->starts_at->setTimezone($timezone)->format('M j, Y · g:i A') }} · {{ $ticket->showtime->screen->name }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="truncate font-medium text-cine-primary">{{ $ticket->booking->user->name }}</p>
                                        <p class="mt-1 truncate text-[10px] text-cine-tertiary">{{ $ticket->booking->user->email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-cine-secondary">{{ $ticket->bookingItem->seat_label }}</td>
                                    <td class="px-4 py-3"><x-admin.badge :status="$ticketTone">{{ str_replace('_', ' ', ucfirst($ticket->status->value)) }}</x-admin.badge></td>
                                    <td class="px-4 py-3 text-cine-secondary">{{ number_format($ticket->scans_count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-cine-border/10 px-4 py-3 sm:px-5">{{ $tickets->links() }}</div>
            @endif
        @else
            @if ($scans->isEmpty())
                <div class="p-8">
                    <x-admin.empty-state icon="scan-line" title="No scan activity found" text="Try changing the filters or search terms." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="admin-data-table w-full min-w-[880px] table-fixed divide-y divide-cine-border/10">
                        <thead>
                            <tr>
                                <th class="w-[19%] px-4 py-3 text-left">Scanned at</th>
                                <th class="w-[17%] px-4 py-3 text-left">Ticket</th>
                                <th class="w-[22%] px-4 py-3 text-left">Movie / Showtime</th>
                                <th class="w-[16%] px-4 py-3 text-left">Customer</th>
                                <th class="w-[13%] px-4 py-3 text-left">Staff</th>
                                <th class="w-[13%] px-4 py-3 text-left">Result</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cine-border/10">
                            @foreach ($scans as $scan)
                                @php
                                    $scanTone = match ($scan->result->value) {
                                        'admitted' => 'success',
                                        'already_used', 'too_early' => 'warning',
                                        default => 'danger',
                                    };
                                    $scanMovie = $scan->showtime?->movie;
                                    $scanShowtime = $scan->showtime;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-cine-primary">{{ $scan->scanned_at->setTimezone($timezone)->format('M j, Y') }}</p>
                                        <p class="mt-1 text-[10px] text-cine-tertiary">{{ $scan->scanned_at->setTimezone($timezone)->format('g:i:s A') }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-cine-primary">{{ $scan->ticket?->ticket_number ?? 'Unknown ticket' }}</p>
                                        <p class="mt-1 text-[10px] text-cine-tertiary">{{ $scan->ticket?->booking?->reference ?? 'No booking linked' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="truncate font-medium text-cine-primary">{{ $scanMovie?->title ?? 'Showtime unavailable' }}</p>
                                        <p class="mt-1 text-[10px] text-cine-tertiary">{{ $scanShowtime?->starts_at?->setTimezone($timezone)->format('M j, Y · g:i A') ?? '—' }}{{ $scanShowtime?->screen ? ' · '.$scanShowtime->screen->name : '' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="truncate font-medium text-cine-primary">{{ $scan->ticket?->booking?->user?->name ?? '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-cine-secondary">{{ $scan->scannedBy->name }}</td>
                                    <td class="px-4 py-3">
                                        <x-admin.badge :status="$scanTone">{{ str_replace('_', ' ', ucfirst($scan->result->value)) }}</x-admin.badge>
                                        @if ($scan->message)
                                            <p class="mt-1 max-w-[150px] truncate text-[10px] text-cine-tertiary" title="{{ $scan->message }}">{{ $scan->message }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-cine-border/10 px-4 py-3 sm:px-5">{{ $scans->links() }}</div>
            @endif
        @endif
    </x-admin.card>
</x-admin.layout>
