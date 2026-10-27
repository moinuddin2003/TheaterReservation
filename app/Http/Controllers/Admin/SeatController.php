<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateScreenSeatsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateSeatsRequest;
use App\Http\Requests\SeatUpdateRequest;
use App\Models\Screen;
use App\Models\Seat;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SeatController extends Controller
{
    public function __construct(private readonly GenerateScreenSeatsAction $generateSeats) {}

    /**
     * Generate (or regenerate) the seat grid for a screen.
     *
     * Blocked while the screen has showtimes, because seats referenced by
     * holds or bookings cannot be removed (restrict on delete).
     */
    public function generate(GenerateSeatsRequest $request, Screen $screen, AuditLogger $auditLogger): RedirectResponse
    {
        if ($screen->showtimes()->exists()) {
            return redirect()
                ->route('admin.screens.show', $screen)
                ->with('error', "Seats for \"{$screen->name}\" cannot be regenerated while it has showtimes.");
        }

        $created = DB::transaction(function () use ($request, $screen, $auditLogger): int {
            $before = [
                'screen_id' => $screen->id,
                'seats' => $this->seatAuditData($screen),
            ];
            $premiumRows = $request->validated('premium_rows', []);
            $created = $this->generateSeats->execute($screen, $premiumRows);
            $auditLogger->logResource(
                $request->user(),
                'seat.grid_regenerated',
                'SeatGrid',
                $screen->id,
                $before,
                [
                    'screen_id' => $screen->id,
                    'rows_count' => $screen->rows_count,
                    'seats_per_row' => $screen->seats_per_row,
                    'premium_rows' => $premiumRows,
                    'seats' => $this->seatAuditData($screen),
                ],
                $request->ip(),
            );

            return $created;
        });

        return redirect()
            ->route('admin.screens.show', $screen)
            ->with('status', "Generated {$created} seats for \"{$screen->name}\".");
    }

    /**
     * Update a single seat (type and/or disabled state).
     */
    public function update(SeatUpdateRequest $request, Screen $screen, Seat $seat, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($seat->screen_id === $screen->id, 404);

        DB::transaction(function () use ($request, $seat, $auditLogger): void {
            $before = $seat->only(['screen_id', 'row_label', 'seat_number', 'label', 'type', 'is_disabled']);
            $seat->update($request->validated());
            $auditLogger->log(
                $request->user(),
                'seat.updated',
                $seat,
                $before,
                $seat->only(['screen_id', 'row_label', 'seat_number', 'label', 'type', 'is_disabled']),
                $request->ip(),
            );
        });

        return redirect()
            ->route('admin.screens.show', $screen)
            ->with('status', "Seat \"{$seat->label}\" was updated.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seatAuditData(Screen $screen): array
    {
        return $screen->seats()
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->map(fn (Seat $seat): array => [
                'id' => $seat->id,
                'label' => $seat->label,
                'type' => $seat->type->value,
                'is_disabled' => $seat->is_disabled,
            ])
            ->all();
    }
}
