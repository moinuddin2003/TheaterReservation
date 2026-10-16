<x-admin.layout title="Tickets & Scans" breadcrumb="Entry">
    <x-admin.page-header title="Tickets & Scans" subtitle="Find tickets, review QR tokens, and inspect staff entry scan results." />
    <x-admin.toast />
    <nav class="mb-5 flex gap-2" aria-label="Ticket views">
        <a href="{{ route('admin.tickets.index', request()->except(['page', 'tab', 'result', 'staff'])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $tab === 'tickets' ? 'border-cine-accent/50 bg-cine-accent/10 text-cine-accent' : 'border-cine-border/10 bg-cine-surface text-cine-secondary' }}">Tickets</a>
        <a href="{{ route('admin.tickets.index', array_merge(request()->except(['page', 'tab', 'status']), ['tab' => 'scans'])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $tab === 'scans' ? 'border-cine-accent/50 bg-cine-accent/10 text-cine-accent' : 'border-cine-border/10 bg-cine-surface text-cine-secondary' }}">Scan log</a>
    </nav>
    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4 sm:grid-cols-2 xl:grid-cols-5">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <label class="text-xs font-semibold text-cine-secondary">Search<input name="q" value="{{ request('q') }}" placeholder="Ticket number, token or booking" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        @if($tab === 'tickets')
            <label class="text-xs font-semibold text-cine-secondary">Status<select name="status" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All statuses</option>@foreach(\App\Enums\TicketStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str($status->value)->title() }}</option>@endforeach</select></label>
        @else
            <label class="text-xs font-semibold text-cine-secondary">Result<select name="result" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All results</option>@foreach(\App\Enums\ScanResult::cases() as $result)<option value="{{ $result->value }}" @selected(request('result') === $result->value)>{{ str($result->value)->title() }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-cine-secondary">Staff<select name="staff" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All staff</option>@foreach($staffUsers as $staff)<option value="{{ $staff->id }}" @selected(request('staff') == $staff->id)>{{ $staff->name }}</option>@endforeach</select></label>
        @endif
        @if($tab === 'tickets')
            <label class="text-xs font-semibold text-cine-secondary">Sort<select name="sort" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">@foreach(['created_at' => 'Issued', 'status' => 'Status', 'ticket_number' => 'Ticket'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'created_at') === $key)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-cine-secondary">Direction<select name="direction" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('direction') === 'asc')>Ascending</option></select></label>
        @endif
        <label class="text-xs font-semibold text-cine-secondary">From<input type="date" name="from" value="{{ request('from') }}" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <label class="text-xs font-semibold text-cine-secondary">To<input type="date" name="to" value="{{ request('to') }}" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
        <div class="flex items-end gap-2"><x-admin.button type="submit">Apply</x-admin.button><x-admin.button href="{{ route('admin.tickets.index', ['tab' => $tab]) }}" variant="secondary">Clear</x-admin.button></div>
    </form>
    @if($tab === 'tickets')
        <x-admin.data-table :rows="$tickets" :columns="[
            ['label' => 'Ticket', 'key' => 'ticket_number'],
            ['label' => 'Token', 'slot' => fn ($row) => substr($row->token, 0, 8).'…'.substr($row->token, -6)],
            ['label' => 'Seat', 'slot' => fn ($row) => $row->bookingItem?->seat_label ?? '—'],
            ['label' => 'Booking', 'slot' => fn ($row) => $row->booking->reference],
            ['label' => 'Showtime', 'slot' => fn ($row) => $row->showtime->movie->title.' · '.$row->showtime->starts_at->timezone($timezone)->format('M j, g:i A')],
            ['label' => 'Status', 'slot' => fn ($row) => $row->status->value],
            ['label' => 'Scans', 'key' => 'scans_count'],
            ['label' => 'QR preview', 'type' => 'link', 'route' => 'admin.tickets.show'],
        ]" empty-title="No tickets found" empty-text="Try a different search or filter." />
    @else
        <x-admin.data-table :rows="$scans" :columns="[
            ['label' => 'Time', 'slot' => fn ($row) => $row->scanned_at->timezone($timezone)->format('M j, Y g:i:s A')],
            ['label' => 'Staff', 'slot' => fn ($row) => $row->scanner->name],
            ['label' => 'Result', 'slot' => fn ($row) => $row->result->value],
            ['label' => 'Ticket', 'type' => 'relation-link', 'route' => 'admin.tickets.show', 'relation' => 'ticket', 'label_key' => 'ticket.ticket_number'],
            ['label' => 'Message', 'key' => 'message'],
            ['label' => 'Booking', 'slot' => fn ($row) => $row->ticket?->booking?->reference ?? '—'],
        ]" empty-title="No scans found" empty-text="No scans match this search and filter." />
    @endif
</x-admin.layout>
