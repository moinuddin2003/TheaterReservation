<?php

namespace App\Http\Controllers\Staff;

use App\Contracts\Staff\StaffData;
use App\Http\Controllers\Controller;
use App\Services\Staff\TodayShowsService;
use Illuminate\Contracts\View\View;

class TodayController extends Controller
{
    public function __invoke(StaffData $data): View
    {
        $now = $data->now();

        return view('staff.today', [
            'overview' => TodayShowsService::fromConfig()->build($data->shows($now), $now),
            'now' => $now,
            'venue' => $data->venue(),
        ]);
    }
}
