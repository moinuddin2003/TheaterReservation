<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Bookings',
            'pageSubtitle' => 'Review customer reservations and booking activity.',
            'breadcrumb' => 'Sales',
            'icon' => 'ticket',
        ]);
    }
}
