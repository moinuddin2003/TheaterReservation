<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Showtimes',
            'pageSubtitle' => 'Schedule screenings across your cinema screens.',
            'breadcrumb' => 'Cinema',
            'icon' => 'calendar-clock',
        ]);
    }
}
