<?php

namespace App\Http\Controllers\Staff;

use App\Contracts\Staff\StaffData;
use App\Contracts\Staff\TicketScanner;
use App\Enums\ScanResult;
use App\Http\Controllers\Controller;
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
     * Which data source is used (mock or database) is decided in StaffServiceProvider.
     * ScanRejectionView only needs the result facts, so it works with both.
     */
    public function index(Request $request, StaffData $data, TicketScanner $scanner, ScanScreenService $screen, ScanRejectionView $rejections): View
    {
        $result = $request->session()->get('scan_result');
        $now = isset($result['at']) ? new DateTimeImmutable($result['at']) : $data->now();
        $overview = TodayShowsService::fromConfig()->build($data->shows($now), $now);

        $scan = $screen->build(
            $overview,
            $request->integer('show') ?: ($result['show_id'] ?? null),
            $data->recentAttempts($now),
        );

        if ($result) {
            $view = ['scan' => $scanner->applyTo($scan, $result), 'now' => $now];

            return $result['status'] === ScanResult::Admitted->value
                ? view('staff.scan-result', $view + ['result' => $result])
                : view('staff.scan-rejected', $view + ['rejection' => $rejections->make($result)]);
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
    public function store(Request $request, TicketScanner $scanner): RedirectResponse
    {
        $showId = $request->integer('show') ?: null;

        $result = $scanner->scan((string) $request->input('token'), $showId, $request->user()->id, $request->ip());

        $request->session()->flash('scan_result', $result);

        return redirect()->route('staff.scan', ['show' => $showId]);
    }
}
