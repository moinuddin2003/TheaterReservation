<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateScreenSeatsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateSeatsRequest;
use App\Http\Requests\SeatUpdateRequest;
use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Http\RedirectResponse;

class SeatController extends Controller
{
    public function __construct(private readonly GenerateScreenSeatsAction $generateSeats) {}

    /**
     * Generate (or regenerate) the seat grid for a screen.
     *
     * Blocked while the screen has showtimes, because seats referenced by
     * holds or bookings cannot be removed (restrict on delete).
     */
    public function generate(GenerateSeatsRequest $request, Screen $screen): RedirectResponse
    {
        if ($screen->showtimes()->exists()) {
            return redirect()
                ->route('admin.screens.show', $screen)
                ->with('error', "Seats for \"{$screen->name}\" cannot be regenerated while it has showtimes.");
        }

        $created = $this->generateSeats->execute($screen, $request->validated('premium_rows', []));

        return redirect()
            ->route('admin.screens.show', $screen)
            ->with('status', "Generated {$created} seats for \"{$screen->name}\".");
    }

    /**
     * Update a single seat (type and/or disabled state).
     */
    public function update(SeatUpdateRequest $request, Screen $screen, Seat $seat): RedirectResponse
    {
        abort_unless($seat->screen_id === $screen->id, 404);

        $seat->update($request->validated());

        return redirect()
            ->route('admin.screens.show', $screen)
            ->with('status', "Seat \"{$seat->label}\" was updated.");
    }
}
