<x-admin.layout title="Dashboard" breadcrumb="Overview">
    <x-admin.page-header
        title="Good to see you, {{ auth()->user()->name }}"
        subtitle="Your CineBook control room at a glance."
    />

    @php
        // TODO: Replace these static placeholders with live admin dashboard metrics.
        $stats = [
            ['label' => "Today's bookings", 'value' => '—', 'note' => 'Bookings created today', 'icon' => 'ticket'],
            ['label' => 'Revenue today', 'value' => '$0.00', 'note' => 'Collected today (USD)', 'icon' => 'dollar-sign'],
            ['label' => 'Upcoming showtimes', 'value' => '—', 'note' => 'Scheduled screenings', 'icon' => 'calendar-clock'],
            ['label' => 'Pending refunds', 'value' => '0', 'note' => 'Awaiting review', 'icon' => 'undo-2'],
        ];
    @endphp

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <x-admin.card class="group transition-colors duration-150 hover:border-cine-accent/25">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium text-cine-secondary">{{ $stat['label'] }}</p>
                        <p class="mt-3 text-2xl font-bold tracking-tight text-cine-primary">{{ $stat['value'] }}</p>
                        <p class="mt-2 text-xs text-cine-tertiary">{{ $stat['note'] }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
                        <x-admin.icon :name="$stat['icon']" class="h-5 w-5" />
                    </span>
                </div>
            </x-admin.card>
        @endforeach
    </div>

    <x-admin.card>
        <x-admin.empty-state
            icon="film"
            title="Coming soon"
            text="Dashboard activity and performance summaries will appear here."
        />
    </x-admin.card>
</x-admin.layout>
