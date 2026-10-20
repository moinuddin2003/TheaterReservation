<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\RosterService;
use App\Services\Staff\TodayShowsService;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    /**
     * Mock data for now: swap the staff-mock / staff-bookings-mock config()
     * reads for database queries when the staff pages use the models.
     */
    public function __invoke(Request $request, RosterService $roster): View
    {
        $now = new DateTimeImmutable(config('staff-mock.now'));
        $overview = TodayShowsService::fromConfig()->build(config('staff-mock.shows'), $now);

        return view('staff.roster', [
            'roster' => $roster->build(
                $overview,
                config('staff-bookings-mock.bookings'),
                $request->integer('show') ?: null,
                $request->string('q')->trim()->toString(),
                $request->integer('page', 1),
            ),
            'now' => $now,
        ]);
    }
}
