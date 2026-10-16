<x-admin.layout title="{{ $user->name }}" breadcrumb="Users">
    <x-admin.page-header :title="$user->name" subtitle="Account profile and booking history.">
        <x-slot:actions><x-admin.button href="{{ route('admin.users.index', ['tab' => $user->role->value === 'staff' ? 'staff' : 'customers']) }}" variant="secondary">Back to users</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />
    @if($errors->any())<div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ $errors->first() }}</div>@endif
    <x-admin.card class="mb-5">
        <div class="flex flex-wrap items-start justify-between gap-5"><div><p class="text-lg font-semibold text-cine-primary">{{ $user->name }}</p><p class="text-sm text-cine-secondary">{{ $user->email }} · {{ $user->role->value }}</p><p class="mt-2 text-xs text-cine-tertiary">Joined {{ $user->created_at->timezone($timezone)->format('M j, Y') }} · {{ $user->bookings_count }} bookings</p></div>
            <x-admin.confirm-dialog :action="route('admin.users.active', $user)" method="PATCH" :hidden="['active' => $active ? '0' : '1']" :title="$active ? 'Deactivate account' : 'Reactivate account'" :message="$active ? 'The user will no longer be able to sign in.' : 'Allow this user to sign in again.'"><x-admin.button :variant="$active ? 'danger' : 'primary'" x-on:click="open = true">{{ $active ? 'Deactivate account' : 'Reactivate account' }}</x-admin.button></x-admin.confirm-dialog>
        </div>
    </x-admin.card>
    <h2 class="mb-3 text-lg font-semibold text-cine-primary">Booking history</h2>
    <x-admin.data-table :rows="$bookings" :columns="[
        ['label' => 'Code', 'key' => 'reference'],
        ['label' => 'Movie', 'slot' => fn ($row) => $row->showtime->movie->title],
        ['label' => 'Status', 'slot' => fn ($row) => $row->status->value],
        ['label' => 'Total', 'slot' => fn ($row) => '$'.number_format($row->total_cents / 100, 2)],
        ['label' => 'Created', 'slot' => fn ($row) => $row->created_at->timezone($timezone)->format('M j, Y g:i A')],
        ['label' => 'Details', 'type' => 'link', 'route' => 'admin.bookings.show'],
    ]" empty-title="No booking history" empty-text="This account has not made any bookings." />
</x-admin.layout>
