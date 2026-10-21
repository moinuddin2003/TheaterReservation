<?php

namespace App\Http\Controllers\Staff;

use App\Contracts\Staff\StaffData;
use App\Http\Controllers\Controller;
use App\Services\Staff\BookingLookupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function __invoke(Request $request, StaffData $data): View
    {
        $lookup = BookingLookupService::fromConfig();
        $reference = $lookup->normalize($request->string('reference')->toString());
        $booking = $reference === '' ? null : $data->findBooking($reference);

        return view('staff.lookup', [
            'lookup' => $lookup->build($reference, $booking),
            'now' => $data->now(),
            'venue' => config('cinema.venue'),
        ]);
    }
}
