<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ToggleSeatDisabledAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ToggleSeatDisabledRequest;
use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SeatController extends Controller
{
    public function index(Screen $screen): View
    {
        $screen->load(['cinema', 'seats' => fn ($query) => $query->orderBy('row_label')->orderBy('seat_number')]);

        return view('admin.screens.seats', compact('screen'));
    }

    public function toggleDisabled(ToggleSeatDisabledRequest $request, Seat $seat, ToggleSeatDisabledAction $action): RedirectResponse
    {
        $action->execute($seat);

        return back()->with('status', 'Seat availability updated.');
    }
}
