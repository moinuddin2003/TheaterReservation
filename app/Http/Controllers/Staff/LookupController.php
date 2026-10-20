<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\BookingLookupService;
use App\Services\Staff\MockLookupBookings;
use App\Services\Staff\TodayShowsService;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * Mock data for now. To use the database, replace the three mock reads
     * below: the shows (staff-mock.shows), the bookings (MockLookupBookings
     * over staff-bookings-mock) and now(). BookingLookupService stays as is
     * as long as each booking has the shape described on that class.
     */
    public function __invoke(Request $request, BookingLookupService $lookup): View
    {
        $now = new DateTimeImmutable(config('staff-mock.now'));
        $overview = TodayShowsService::fromConfig()->build(config('staff-mock.shows'), $now);
        $bookings = MockLookupBookings::fromConfig()->toLookupBookings(config('staff-bookings-mock.bookings'));

        return view('staff.lookup', [
            'lookup' => $lookup->build($request->string('reference')->toString(), $bookings, $overview['rows']),
            'now' => $now,
            'venue' => config('cinema.venue'),
        ]);
    }
}
