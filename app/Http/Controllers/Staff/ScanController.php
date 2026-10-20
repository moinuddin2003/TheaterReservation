<?php

namespace App\Http\Controllers\Staff;

use App\Enums\ScanResult;
use App\Http\Controllers\Controller;
use App\Services\Staff\MockTicketScanner;
use App\Services\Staff\ScanRejectionView;
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
     * ScanRejectionView only needs the result facts, so it stays.
     */
    public function index(Request $request, ScanScreenService $screen, ScanRejectionView $rejections): View
    {
        $scanner = MockTicketScanner::fromConfig();
        $result = $request->session()->get('scan_result');
        $now = new DateTimeImmutable($result['at'] ?? config('staff-mock.now'));
        $overview = TodayShowsService::fromConfig()->build(config('staff-mock.shows'), $now);

        $scan = $screen->build(
            $overview,
            $request->integer('show') ?: ($result['show_id'] ?? null),
            config('staff-mock.scan_attempts'),
        );

        if ($result) {
            $data = ['scan' => $scanner->applyTo($scan, $result), 'now' => $now];

            return $result['status'] === ScanResult::Admitted->value
                ? view('staff.scan-result', $data + ['result' => $result])
                : view('staff.scan-rejected', $data + ['rejection' => $rejections->make($result)]);
        }

        return view('staff.scan', [
            'scan' => $scan,
            'now' => $now,
        ]);
    }

    /**
     * Checks the token and returns to the scan screen. The result (an admission
     * or a rejection) is shown once (flashed), so reloading the page never repeats it.
     */
    public function store(Request $request): RedirectResponse
    {
        $showId = $request->integer('show') ?: null;

        $result = MockTicketScanner::fromConfig()->scan(
            (string) $request->input('token'),
            config('staff-mock.tickets'),
            config('staff-mock.shows'),
            new DateTimeImmutable(config('staff-mock.scan_at')),
            $showId,
        );

        $request->session()->flash('scan_result', $result);

        return redirect()->route('staff.scan', ['show' => $showId]);
    }
}
