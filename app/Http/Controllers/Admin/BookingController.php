<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Bookings',
            'Review customer reservations and booking activity.',
            'Sales',
            'ticket',
            Booking::query()->with(['user', 'showtime.movie']),
            ['reference', 'user.name', 'user.email', 'showtime.movie.title'],
            ['created_at', 'status', 'total_cents'],
            [
                ['label' => 'Reference', 'key' => 'reference'],
                ['label' => 'Customer', 'key' => 'user.name'],
                ['label' => 'Movie', 'key' => 'showtime.movie.title'],
                ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
                ['label' => 'Total', 'key' => 'total_cents', 'type' => 'money'],
                ['label' => 'Created', 'key' => 'created_at'],
            ],
            [
                ['name' => 'status', 'label' => 'status', 'options' => [
                    'pending_payment' => 'Pending payment',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'expired' => 'Expired',
                    'failed' => 'Failed',
                ]],
            ],
            ['status' => 'status'],
        );
    }
}
