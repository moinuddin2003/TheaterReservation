<x-admin.layout title="Stripe event {{ $event->stripe_event_id }}" breadcrumb="Stripe Events">
    <x-admin.page-header title="Stripe event details" subtitle="{{ $event->type }}">
        <x-slot:actions><x-admin.button href="{{ route('admin.stripe-events.index') }}" variant="secondary">Back to events</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />
    <x-admin.card class="max-w-4xl">
        <dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-xs font-semibold text-cine-tertiary">Event ID</dt><dd class="mt-1 break-all font-mono text-sm text-cine-primary">{{ $event->stripe_event_id }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Type</dt><dd class="mt-1 text-sm text-cine-primary">{{ $event->type }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Status</dt><dd class="mt-1 text-sm text-cine-primary">{{ $event->status }}</dd></div><div><dt class="text-xs font-semibold text-cine-tertiary">Received / processed</dt><dd class="mt-1 text-sm text-cine-primary">{{ $event->created_at->timezone($timezone)->format('M j, Y g:i:s A') }} / {{ $event->processed_at?->timezone($timezone)->format('M j, Y g:i:s A') ?? '—' }}</dd></div></dl>
        @if($event->error_message)<div class="mt-5 rounded-xl bg-cine-danger/10 p-4 text-sm text-cine-danger">{{ $event->error_message }}</div>@endif
        <div class="mt-5"><h2 class="font-semibold text-cine-primary">Persisted event record</h2><p class="mt-1 text-sm text-cine-secondary">The current schema does not store Stripe's event payload, so only the fields below are available for inspection.</p><pre class="mt-3 overflow-x-auto rounded-xl bg-cine-bg p-4 text-xs text-cine-primary">{{ json_encode($event->only(['stripe_event_id', 'type', 'status', 'error_message', 'processed_at', 'created_at', 'updated_at']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
        @if($event->status === 'failed')<x-admin.confirm-dialog :action="route('admin.stripe-events.replay', $event)" title="Replay failed event" message="Queue this Stripe event for processing again?"><x-admin.button class="mt-5" x-on:click="open = true">Replay event</x-admin.button></x-admin.confirm-dialog>@endif
    </x-admin.card>
</x-admin.layout>
