<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\TodayShowsService;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;

class TodayController extends Controller
{
    /**
     * Mock data for now: swap the two staff-mock config() reads for a
     * database query and now() when the staff pages use the models.
     */
    public function __invoke(): View
    {
        $now = new DateTimeImmutable(config('staff-mock.now'));

        return view('staff.today', [
            'overview' => TodayShowsService::fromConfig()->build(config('staff-mock.shows'), $now),
            'now' => $now,
            'venue' => config('staff-mock.venue'),
        ]);
    }
}
