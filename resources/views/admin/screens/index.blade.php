<x-admin.layout title="Screens" breadcrumb="CineBook / Admin / Cinema">
    <x-admin.page-header title="Screens" subtitle="Review auditorium layouts, capacity, and availability by cinema.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.screens.create') }}"><span aria-hidden="true">+</span>Add screen</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-cine-secondary"><span class="font-bold text-cine-primary">{{ $screens->count() }}</span> {{ Str::plural('screen', $screens->count()) }} configured</p>
        <a href="{{ route('admin.cinema.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-cine-accent hover:text-cine-primary">
            <x-admin.icon name="arrow-left" class="h-3.5 w-3.5" />
            Back to cinemas
        </a>
    </div>

    @if ($screens->isEmpty())
        <x-admin.card><x-admin.empty-state icon="monitor" title="No screens yet" text="Create a cinema first, then add its first auditorium." /></x-admin.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach ($screens as $screen)
                @php($capacity = $screen->rows_count * $screen->seats_per_row)
                <x-admin.card class="!overflow-hidden !p-0">
                    <div class="border-b border-cine-border/10 bg-gradient-to-br from-cine-elevated/70 to-cine-surface p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
                                    <x-admin.icon name="monitor" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-bold text-cine-primary">{{ $screen->name }}</h3>
                                    <p class="mt-1 flex items-center gap-1.5 truncate text-xs text-cine-secondary">
                                        <x-admin.icon name="building" class="h-3.5 w-3.5 shrink-0 text-cine-tertiary" />
                                        {{ $screen->cinema->name }}
                                    </p>
                                </div>
                            </div>
                            <x-admin.badge :status="$screen->is_active ? 'success' : 'warning'">{{ $screen->is_active ? 'Active' : 'Inactive' }}</x-admin.badge>
                        </div>
                        <div class="mt-5 flex items-end justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-cine-tertiary">Auditorium capacity</p>
                                <p class="mt-1 text-2xl font-bold tracking-tight text-cine-primary">{{ number_format($capacity) }} <span class="text-xs font-medium text-cine-secondary">seats</span></p>
                            </div>
                            <p class="text-right text-xs text-cine-secondary">{{ $screen->rows_count }} rows <span aria-hidden="true">×</span> {{ $screen->seats_per_row }} per row</p>
                        </div>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="rounded-xl border border-cine-border/10 bg-cine-bg/40 px-4 py-3" role="img" aria-label="Seat layout preview: {{ $screen->rows_count }} rows and {{ $screen->seats_per_row }} seats per row">
                            <div class="mx-auto mb-3 h-1 w-2/3 rounded-full bg-gradient-to-r from-transparent via-cine-accent/60 to-transparent"></div>
                            <div class="space-y-1.5">
                                @for ($rowIndex = 0; $rowIndex < min(4, $screen->rows_count); $rowIndex++)
                                    <div class="flex justify-center gap-1">
                                        @for ($seatIndex = 0; $seatIndex < min(12, $screen->seats_per_row); $seatIndex++)
                                            <span class="h-2 flex-1 max-w-5 rounded-sm {{ $screen->seats_count ? (($rowIndex * min(12, $screen->seats_per_row) + $seatIndex) < $screen->disabled_seats_count ? 'bg-cine-disabled' : 'bg-cine-accent/70') : 'bg-cine-border/20' }}"></span>
                                        @endfor
                                    </div>
                                @endfor
                            </div>
                            <p class="mt-2 text-center text-[9px] font-semibold uppercase tracking-[0.18em] text-cine-tertiary">Screen</p>
                        </div>
                        <div class="grid grid-cols-3 divide-x divide-cine-border/10 rounded-xl border border-cine-border/10 bg-cine-bg/30 py-3 text-center">
                            <div><p class="text-lg font-bold text-cine-primary">{{ number_format($screen->seats_count) }}</p><p class="mt-0.5 text-[10px] text-cine-tertiary">Generated</p></div>
                            <div><p class="text-lg font-bold text-cine-warning">{{ number_format($screen->premium_seats_count) }}</p><p class="mt-0.5 text-[10px] text-cine-tertiary">Premium</p></div>
                            <div><p class="text-lg font-bold text-cine-danger">{{ number_format($screen->disabled_seats_count) }}</p><p class="mt-0.5 text-[10px] text-cine-tertiary">Unavailable</p></div>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-cine-border/10 pt-4">
                            <p class="text-xs text-cine-secondary">{{ number_format($screen->showtimes_count) }} {{ Str::plural('showtime', $screen->showtimes_count) }}</p>
                            <div class="flex gap-2">
                                <x-admin.button :href="route('admin.screens.show', $screen)" class="!min-h-9 !rounded-xl !px-4 !py-1.5">Manage seats</x-admin.button>
                                <x-admin.button :href="route('admin.screens.edit', $screen)" variant="secondary">Edit</x-admin.button>
                            </div>
                        </div>
                    </div>
                </x-admin.card>
            @endforeach
        </div>
    @endif
</x-admin.layout>
