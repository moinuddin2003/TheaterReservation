<x-admin.layout title="Bookings" breadcrumb="Sales">
    <x-admin.page-header title="Bookings" subtitle="Search reservations, inspect payment state, and manage customer bookings." />
    <x-admin.toast />
    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4 sm:grid-cols-2 xl:grid-cols-4">
        <label class="text-xs font-semibold text-cine-secondary">Search
            <input name="q" value="{{ request('q') }}" placeholder="Code, customer or email" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Status
            <select name="status" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All statuses</option>@foreach (\App\Enums\BookingStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>@endforeach</select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Movie
            <select name="movie" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All movies</option>@foreach ($movies as $movie)<option value="{{ $movie->id }}" @selected(request('movie') == $movie->id)>{{ $movie->title }}</option>@endforeach</select>
        </label>
        <div class="grid grid-cols-2 gap-2">
            <label class="text-xs font-semibold text-cine-secondary">From<input type="date" name="from" value="{{ request('from') }}" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-2 text-sm text-cine-primary"></label>
            <label class="text-xs font-semibold text-cine-secondary">To<input type="date" name="to" value="{{ request('to') }}" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-2 text-sm text-cine-primary"></label>
        </div>
        <label class="text-xs font-semibold text-cine-secondary">Sort by
            <select name="sort" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">@foreach (['created_at' => 'Created', 'status' => 'Status', 'total_cents' => 'Total', 'reference' => 'Code'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'created_at') === $key)>{{ $label }}</option>@endforeach</select>
        </label>
        <label class="text-xs font-semibold text-cine-secondary">Direction
            <select name="direction" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Newest first</option><option value="asc" @selected(request('direction') === 'asc')>Oldest first</option></select>
        </label>
        <div class="flex items-end gap-2"><x-admin.button type="submit">Apply filters</x-admin.button><x-admin.button href="{{ route('admin.bookings.index') }}" variant="secondary">Clear</x-admin.button></div>
    </form>
    <x-admin.data-table :rows="$bookings" :columns="[
        ['label' => 'Code', 'key' => 'reference'],
        ['label' => 'Customer', 'slot' => fn ($row) => $row->user->name.' · '.$row->user->email],
        ['label' => 'Movie & time', 'slot' => fn ($row) => $row->showtime->movie->title.' · '.$row->showtime->starts_at->timezone($timezone)->format('M j, g:i A')],
        ['label' => 'Seats', 'key' => 'seats_count'],
        ['label' => 'Total', 'slot' => fn ($row) => '$'.number_format($row->total_cents / 100, 2)],
        ['label' => 'Payment', 'slot' => fn ($row) => $row->payments->first()?->status?->value ?? '—'],
        ['label' => 'Status', 'slot' => fn ($row) => $row->status->value],
        ['label' => 'Created', 'slot' => fn ($row) => $row->created_at->timezone($timezone)->format('M j, Y g:i A')],
        ['label' => 'Actions', 'type' => 'link', 'route' => 'admin.bookings.show', 'link_label' => 'Details'],
    ]" empty-title="No bookings found" empty-text="Try another search or clear one or more filters.">
    </x-admin.data-table>
</x-admin.layout>
