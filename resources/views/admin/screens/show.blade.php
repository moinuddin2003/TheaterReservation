<x-admin.layout :title="$screen->name" breadcrumb="CineBook / Admin / Cinema / Screen">
    <x-admin.page-header :title="$screen->name" :subtitle="$screen->cinema->name.' · '.$screen->rows_count.' rows × '.$screen->seats_per_row.' seats'">
        <x-slot:actions>
            <x-admin.button :href="route('admin.screens.index')" variant="secondary">
                <x-admin.icon name="arrow-left" class="h-4 w-4" />
                Back to screens
            </x-admin.button>
            <x-admin.button :href="route('admin.screens.edit', $screen)" variant="secondary">Edit screen</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Seats', $screen->seats_count, 'armchair'],
            ['Bookable', max(0, $screen->seats_count - $screen->disabled_seats_count), 'check-circle'],
            ['Premium', $screen->premium_seats_count, 'star'],
            ['Scheduled showtimes', $screen->showtimes_count, 'calendar'],
        ] as [$label, $value, $icon])
            <div class="rounded-2xl border border-cine-border/10 bg-cine-surface p-4">
                <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-cine-accent/10 text-cine-accent"><x-admin.icon :name="$icon" class="h-4 w-4" /></span><div><p class="text-xs text-cine-secondary">{{ $label }}</p><p class="mt-0.5 text-xl font-bold text-cine-primary">{{ number_format($value) }}</p></div></div>
            </div>
        @endforeach
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(260px,320px)]">
        <x-admin.card class="!p-0">
            <div class="border-b border-cine-border/10 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h3 class="font-semibold text-cine-primary">Seat layout</h3><p class="mt-1 text-xs text-cine-secondary">Select a seat to change its type or availability.</p></div>
                    <div class="flex flex-wrap items-center gap-3 text-[10px] text-cine-secondary">
                        <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded bg-cine-accent"></i>Standard</span>
                        <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded bg-cine-warning"></i>Premium</span>
                        <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded bg-cyan-400"></i>Accessible</span>
                        <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded bg-cine-disabled"></i>Disabled</span>
                    </div>
                </div>
            </div>

            @if ($seatRows->isEmpty())
                <div class="p-5 sm:p-6">
                    <x-admin.empty-state icon="armchair" title="Seat layout not generated" text="Generate seats using the auditorium dimensions. You can mark premium rows before creating the grid." />
                </div>
            @else
                <div class="overflow-x-auto p-4 sm:p-6">
                    <div class="mx-auto mb-8 max-w-2xl rounded-b-[50%] border-t-4 border-cine-accent/60 pb-3 pt-2 text-center text-[10px] font-bold uppercase tracking-[0.35em] text-cine-tertiary">Screen</div>
                    <div class="mx-auto w-max min-w-full space-y-2.5">
                        @foreach ($seatRows as $rowLabel => $seats)
                            <div class="flex items-center justify-center gap-1.5 sm:gap-2">
                                <span class="w-5 text-right text-[10px] font-bold text-cine-tertiary">{{ $rowLabel }}</span>
                                @foreach ($seats as $seat)
                                    <button
                                        type="button"
                                        x-data
                                        @click="$dispatch('edit-seat', { action: '{{ route('admin.screens.seats.update', [$screen, $seat]) }}', label: @js($seat->label), type: @js($seat->type->value), disabled: @js($seat->is_disabled) })"
                                        title="{{ $seat->label }} · {{ $seat->is_disabled ? 'Disabled' : str($seat->type->value)->title() }}"
                                        aria-label="Edit seat {{ $seat->label }}"
                                        class="flex h-8 min-w-8 items-center justify-center rounded-md border px-1 text-[9px] font-semibold sm:h-9 sm:min-w-9 sm:text-[10px] {{ $seat->is_disabled ? 'border-cine-border/10 bg-cine-disabled-bg text-cine-disabled' : match ($seat->type) { \App\Enums\SeatType::Premium => 'border-cine-warning/30 bg-cine-warning/15 text-cine-warning', \App\Enums\SeatType::Accessible => 'border-cyan-400/30 bg-cyan-400/15 text-cyan-300', default => 'border-cine-accent/25 bg-cine-accent/15 text-cine-accent' } }}"
                                    >{{ $seat->seat_number }}</button>
                                @endforeach
                                <span class="w-5 text-[10px] font-bold text-cine-tertiary">{{ $rowLabel }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-8 text-center text-[10px] text-cine-tertiary">Click a seat to edit it</div>
                </div>
            @endif
        </x-admin.card>

        <div class="space-y-5">
            <x-admin.card title="{{ $screen->seats_count > 0 ? 'Regenerate seat grid' : 'Generate seat grid' }}" subtitle="A new grid follows the row and seat dimensions saved on this screen.">
                <form method="POST" action="{{ route('admin.screens.seats.generate', $screen) }}" class="space-y-4" novalidate>
                    @csrf
                    <fieldset>
                        <legend class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Premium rows</legend>
                        <div class="grid grid-cols-6 gap-1.5">
                            @for ($rowIndex = 0; $rowIndex < $screen->rows_count; $rowIndex++)
                                @php($rowName = chr(65 + $rowIndex))
                                <label class="flex cursor-pointer items-center justify-center gap-1 rounded-lg border border-cine-border/10 bg-cine-bg/50 px-1 py-2 text-[10px] text-cine-secondary hover:border-cine-warning/40">
                                    <input type="checkbox" name="premium_rows[]" value="{{ $rowName }}" @checked(in_array($rowName, (array) old('premium_rows', []), true)) class="h-3 w-3 rounded border-cine-border/20 bg-cine-bg text-cine-warning focus:ring-cine-warning" @if ($errors->has('premium_rows') || $errors->has('premium_rows.*')) aria-invalid="true" aria-describedby="premium-rows-error" @endif>
                                    {{ $rowName }}
                                </label>
                            @endfor
                        </div>
                        <x-admin.field-error field="premium_rows.*" id="premium-rows-error" />
                    </fieldset>
                    @if ($screen->showtimes_count > 0)
                        <p class="rounded-xl border border-cine-warning/20 bg-cine-warning/10 p-3 text-xs leading-5 text-cine-warning">Seat grids cannot be regenerated while this screen has showtimes.</p>
                    @else
                        <x-admin.button type="submit" class="w-full justify-center">{{ $screen->seats_count > 0 ? 'Regenerate seats' : 'Generate seats' }}</x-admin.button>
                    @endif
                </form>
            </x-admin.card>

            <x-admin.card title="Screen details">
                <dl class="space-y-3 text-xs">
                    <div class="flex justify-between gap-3"><dt class="text-cine-tertiary">Cinema</dt><dd class="text-right font-semibold text-cine-primary">{{ $screen->cinema->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-cine-tertiary">Dimensions</dt><dd class="font-semibold text-cine-primary">{{ $screen->rows_count }} × {{ $screen->seats_per_row }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-cine-tertiary">Availability</dt><dd class="font-semibold {{ $screen->is_active ? 'text-cine-success' : 'text-cine-warning' }}">{{ $screen->is_active ? 'Active' : 'Inactive' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-cine-tertiary">Disabled seats</dt><dd class="font-semibold text-cine-danger">{{ $screen->disabled_seats_count }}</dd></div>
                </dl>
                <form method="POST" action="{{ route('admin.screens.destroy', $screen) }}" class="mt-5 border-t border-cine-border/10 pt-4" onsubmit="return confirm('Delete this screen? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    @if ($screen->showtimes_count > 0)
                        <p class="mb-3 text-xs leading-5 text-cine-warning">This screen cannot be deleted while it has showtimes.</p>
                        <x-admin.button type="submit" variant="danger" class="w-full justify-center opacity-50" disabled>Delete screen</x-admin.button>
                    @else
                        <x-admin.button type="submit" variant="danger" class="w-full justify-center">Delete screen</x-admin.button>
                    @endif
                </form>
            </x-admin.card>
        </div>
    </div>

    <div
        x-data="{ open: @js($errors->has('type') || $errors->has('is_disabled')), action: '', label: '', type: @js(old('type', 'standard')), disabled: @js((bool) old('is_disabled', false)) }"
        @edit-seat.window="open = true; action = $event.detail.action; label = $event.detail.label; type = $event.detail.type; disabled = $event.detail.disabled"
        @keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[150] flex items-end justify-center bg-black/60 p-4 sm:items-center"
        @click.self="open = false"
        role="dialog"
        aria-modal="true"
        aria-label="Edit seat"
    >
        <div class="w-full max-w-sm rounded-2xl border border-cine-border/10 bg-cine-surface p-5 shadow-cine-card">
            <div class="mb-4 flex items-start justify-between">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-cine-accent">Seat settings</p><h3 class="mt-1 text-lg font-bold text-cine-primary" x-text="label"></h3></div>
                <button type="button" @click="open = false" class="rounded-lg px-2 py-1 text-cine-secondary hover:bg-cine-elevated" aria-label="Close">×</button>
            </div>
            <form method="POST" :action="action" class="space-y-4" novalidate>
                @csrf
                @method('PUT')
                <div>
                    <label for="seat-type" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Seat type</label>
                    <select id="seat-type" name="type" x-model="type" class="min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20" @error('type') aria-invalid="true" aria-describedby="seat-type-error" @enderror>
                        @foreach ($seatTypes as $seatType)
                            <option value="{{ $seatType->value }}">{{ str($seatType->value)->title() }}</option>
                        @endforeach
                    </select>
                    <x-admin.field-error field="type" id="seat-type-error" />
                </div>
                <label class="flex items-center gap-3 rounded-xl border border-cine-border/10 bg-cine-bg/50 p-3">
                    <input type="checkbox" name="is_disabled" value="1" x-model="disabled" class="rounded border-cine-border/20 bg-cine-bg text-cine-accent focus:ring-cine-accent" @error('is_disabled') aria-invalid="true" aria-describedby="seat-disabled-error" @enderror>
                    <span><span class="block text-sm font-semibold text-cine-primary">Disable this seat</span><span class="mt-0.5 block text-xs text-cine-secondary">Disabled seats are unavailable for booking.</span></span>
                </label>
                <x-admin.field-error field="is_disabled" id="seat-disabled-error" />
                <div class="flex justify-end gap-2">
                    <x-admin.button type="button" variant="secondary" @click="open = false">Cancel</x-admin.button>
                    <x-admin.button type="submit">Save seat</x-admin.button>
                </div>
            </form>
        </div>
    </div>
</x-admin.layout>
