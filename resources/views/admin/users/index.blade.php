<x-admin.layout title="Users" breadcrumb="People">
    <div x-data="{ createStaffOpen: false }">
        <x-admin.page-header title="Users" subtitle="Manage customer and staff accounts. Admin accounts cannot be created or deactivated here.">
            <x-slot:actions><x-admin.button x-on:click="createStaffOpen = true">Create staff</x-admin.button></x-slot:actions>
        </x-admin.page-header>
        <x-admin.toast />
        @if($errors->any())<div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ $errors->first() }}</div>@endif
        <x-admin.modal name="createStaffOpen" title="Create staff account">
            <form method="POST" action="{{ route('admin.users.staff.store') }}" class="space-y-4" x-data="{saving:false}" x-on:submit="if(saving){$event.preventDefault();return} saving=true">@csrf
                <x-admin.input name="name" label="Name" required autocomplete="name" />
                <x-admin.input name="email" label="Email" type="email" required autocomplete="email" />
                <x-admin.input name="password" label="Temporary password" type="password" required minlength="8" autocomplete="new-password" />
                <p class="text-xs text-cine-secondary">The account is created as staff. No administrator role can be selected.</p>
                <div class="flex justify-end"><x-admin.button type="submit" x-bind:disabled="saving"><span x-show="!saving">Create staff account</span><span x-show="saving" x-cloak>Creating…</span></x-admin.button></div>
            </form>
        </x-admin.modal>
        <nav class="mb-5 flex gap-2" aria-label="User role">
            @foreach(['customers' => 'Customers', 'staff' => 'Staff'] as $key => $label)<a href="{{ route('admin.users.index', array_merge(request()->except(['page', 'tab']), ['tab' => $key])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'border-cine-accent/50 bg-cine-accent/10 text-cine-accent' : 'border-cine-border/10 bg-cine-surface text-cine-secondary' }}">{{ $label }}</a>@endforeach
        </nav>
        <form method="GET" class="mb-5 flex flex-wrap gap-3 rounded-2xl border border-cine-border/10 bg-cine-surface p-4">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="min-w-48 flex-1 text-xs font-semibold text-cine-secondary">Search<input name="q" value="{{ request('q') }}" placeholder="Name or email" class="mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"></label>
            <label class="text-xs font-semibold text-cine-secondary">Account status<select name="state" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="">All</option><option value="active" @selected(request('state') === 'active')>Active</option><option value="inactive" @selected(request('state') === 'inactive')>Inactive</option></select></label>
            <label class="text-xs font-semibold text-cine-secondary">Sort<select name="sort" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">@foreach(['name' => 'Name', 'email' => 'Email', 'created_at' => 'Joined'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'created_at') === $key)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-cine-secondary">Direction<select name="direction" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('direction') === 'asc')>Ascending</option></select></label>
            <div class="flex items-end gap-2"><x-admin.button type="submit">Apply</x-admin.button><x-admin.button href="{{ route('admin.users.index', ['tab' => $tab]) }}" variant="secondary">Clear</x-admin.button></div>
        </form>
        <x-admin.data-table :rows="$users" :columns="[
            ['label' => 'Name', 'key' => 'name'],
            ['label' => 'Email', 'key' => 'email'],
            ['label' => 'Status', 'slot' => fn ($row) => in_array($row->id, $inactiveIds, true) ? 'Inactive' : 'Active'],
            ['label' => 'Bookings', 'key' => 'bookings_count'],
            ['label' => 'Joined', 'slot' => fn ($row) => $row->created_at->timezone($timezone)->format('M j, Y')],
            ['label' => 'Details', 'type' => 'link', 'route' => 'admin.users.show'],
        ]" empty-title="{{ $tab === 'staff' ? 'No staff users' : 'No customers found' }}" empty-text="Try a different search or create a staff account." />
    </div>
</x-admin.layout>
