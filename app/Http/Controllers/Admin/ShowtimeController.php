<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Showtimes',
            'Schedule screenings across your cinema screens.',
            'Cinema',
            'calendar-clock',
            Showtime::query()->with(['movie', 'screen']),
            ['movie.title', 'screen.name'],
            ['starts_at', 'status', 'created_at'],
            [
                ['label' => 'Movie', 'key' => 'movie.title'],
                ['label' => 'Screen', 'key' => 'screen.name'],
                ['label' => 'Starts', 'key' => 'starts_at'],
                ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
                ['label' => 'Base price', 'key' => 'base_price_cents', 'type' => 'money'],
            ],
            [
                ['name' => 'status', 'label' => 'status', 'options' => [
                    'scheduled' => 'Scheduled',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
                ]],
            ],
            ['status' => 'status'],
            'starts_at',
        );
    }
}
