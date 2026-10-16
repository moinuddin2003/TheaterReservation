@props(['seats', 'editable' => false])

<div class="overflow-x-auto rounded-2xl border border-cine-border/10 bg-cine-bg p-4 sm:p-6">
    <div class="mx-auto mb-8 max-w-3xl">
        <div class="h-10 rounded-t-[50%] border-t-2 border-cine-accent/70 bg-gradient-to-b from-cine-accent/10 to-transparent text-center text-xs font-semibold uppercase tracking-[0.35em] text-cine-secondary">Screen</div>
        <div class="flex justify-center gap-4 text-xs text-cine-secondary">
            @foreach (['available', 'held', 'booked', 'disabled'] as $state)
                <span class="inline-flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm {{ $state === 'available' ? 'bg-cine-elevated' : ($state === 'held' ? 'bg-cine-warning' : ($state === 'booked' ? 'bg-cine-accent' : 'bg-cine-disabled')) }}"></i>{{ str($state)->title() }}</span>
            @endforeach
        </div>
    </div>
    <div class="space-y-2">
        @foreach ($seats->groupBy('row_label') as $row => $rowSeats)
            <div class="flex min-w-max items-center justify-center gap-1.5">
                <span class="w-5 text-xs text-cine-tertiary">{{ $row }}</span>
                @foreach ($rowSeats as $seat)
                    @php($state = $seat->occupancy_state ?? ($seat->is_disabled ? 'disabled' : 'available'))
                    @if ($editable)
                        <form method="POST" action="{{ route('admin.seats.toggle-disabled', $seat) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" title="{{ $seat->label }} · {{ $state }} (toggle disabled)" aria-label="Toggle disabled state for seat {{ $seat->label }}" class="h-7 w-7 rounded-md text-[9px] font-semibold transition hover:scale-110 focus-visible:ring-2 focus-visible:ring-cine-accent {{ $state === 'available' ? 'bg-cine-elevated text-cine-primary' : ($state === 'held' ? 'bg-cine-warning text-cine-bg' : ($state === 'booked' ? 'bg-cine-accent text-white' : 'bg-cine-disabled text-cine-primary')) }}">{{ $seat->seat_number }}</button>
                        </form>
                    @else
                        <span title="{{ $seat->label }} · {{ $state }}" aria-label="{{ $seat->label }} {{ $state }}" class="flex h-7 w-7 items-center justify-center rounded-md text-[9px] font-semibold {{ $state === 'available' ? 'bg-cine-elevated text-cine-primary' : ($state === 'held' ? 'bg-cine-warning text-cine-bg' : ($state === 'booked' ? 'bg-cine-accent text-white' : 'bg-cine-disabled text-cine-primary')) }}">{{ $seat->seat_number }}</span>
                    @endif
                @endforeach
                <span class="w-5 text-xs text-cine-tertiary">{{ $row }}</span>
            </div>
        @endforeach
    </div>
</div>
