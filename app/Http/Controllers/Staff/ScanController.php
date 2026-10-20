<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\MockTicketScanner;
use App\Services\Staff\ScanScreenService;
use App\Services\Staff\TodayShowsService;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    /**
     * Mock data for now: swap the staff-mock config() reads and the
     * MockTicketScanner for database queries and ScanTicketAction later.
     */
    public function index(Request $request, ScanScreenService $screen, MockTicketScanner $scanner): View
    {
        $result = $request->session()->get('scan_result');
        $now = new DateTimeImmutable(config($result ? 'staff-mock.scan_at' : 'staff-mock.now'));
        $overview = TodayShowsService::fromConfig()->build(config('staff-mock.shows'), $now);

        $scan = $screen->build(
            $overview,
            $request->integer('show') ?: ($result['show_id'] ?? null),
            config('staff-mock.scan_attempts'),
        );

        if ($result) {
            return view('staff.scan-result', [
                'scan' => $scanner->applyTo($scan, $result),
                'result' => $result,
                'now' => $now,
            ]);
        }

        return view('staff.scan', ['scan' => $scan, 'now' => $now]);
    }

    /**
     * Checks the token and returns to the scan screen. An admission is
     * shown once (flashed), so reloading the page never repeats it.
     */
    public function store(Request $request, MockTicketScanner $scanner): RedirectResponse
    {
        $result = $scanner->scan(
            (string) $request->input('token'),
            config('staff-mock.tickets'),
            config('staff-mock.shows'),
            new DateTimeImmutable(config('staff-mock.scan_at')),
        );

        if ($result) {
            $request->session()->flash('scan_result', $result);
        }

        return redirect()->route('staff.scan', ['show' => $request->input('show')]);
    }
}
