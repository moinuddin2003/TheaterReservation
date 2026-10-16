<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Tickets & Scans',
            'View issued tickets and entry scan history.',
            'Entry',
            'scan-line',
            Ticket::query()->with(['booking', 'showtime.movie'])->withCount('scans'),
            ['ticket_number', 'booking.reference', 'showtime.movie.title'],
            ['created_at', 'status', 'ticket_number'],
            [
                ['label' => 'Ticket', 'key' => 'ticket_number'],
                ['label' => 'Booking', 'key' => 'booking.reference'],
                ['label' => 'Movie', 'key' => 'showtime.movie.title'],
                ['label' => 'Showtime', 'key' => 'showtime.starts_at'],
                ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
                ['label' => 'Scans', 'key' => 'scans_count'],
            ],
            [
                ['name' => 'status', 'label' => 'status', 'options' => [
                    'valid' => 'Valid',
                    'used' => 'Used',
                    'voided' => 'Voided',
                ]],
            ],
            ['status' => 'status'],
        );
    }
}
