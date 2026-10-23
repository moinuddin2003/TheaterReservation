<?php

namespace App\Http\Controllers\Staff;

use App\Contracts\Staff\StaffData;
use App\Http\Controllers\Controller;
use App\Services\Staff\RosterService;
use App\Services\Staff\TodayShowsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    public function __invoke(Request $request, StaffData $data, RosterService $roster): View
    {
        $now = $data->now();
        $overview = TodayShowsService::fromConfig()->build($data->shows($now), $now);

        return view('staff.roster', [
            'roster' => $roster->build(
                $overview,
                $data->rosterBookings($now),
                $request->integer('show') ?: null,
                $request->string('q')->trim()->toString(),
                $request->integer('page', 1),
            ),
            'now' => $now,
        ]);
    }
}
