<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\ScanScreenService;
use App\Services\Staff\TodayShowsService;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    /**
     * Mock data for now: swap the staff-mock config() reads for database
     * queries and now() when the staff pages use the models.
     */
    public function index(Request $request, ScanScreenService $screen): View
    {
        $now = new DateTimeImmutable(config('staff-mock.now'));
        $overview = TodayShowsService::fromConfig()->build(config('staff-mock.shows'), $now);

        return view('staff.scan', [
            'scan' => $screen->build($overview, $request->integer('show') ?: null, config('staff-mock.scan_attempts')),
            'now' => $now,
        ]);
    }

    /**
     * Placeholder so the form has a target: scan handling and the result
     * display come with the next screen, so it just returns to the scan page.
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('staff.scan', ['show' => $request->input('show')]);
    }
}
