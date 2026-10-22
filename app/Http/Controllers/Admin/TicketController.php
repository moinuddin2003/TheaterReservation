<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Tickets & Scans',
            'pageSubtitle' => 'View issued tickets and entry scan history.',
            'breadcrumb' => 'Entry',
            'icon' => 'scan-line',
        ]);
    }
}
