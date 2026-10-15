<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CinemaController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Cinema & Screens',
            'pageSubtitle' => 'Manage cinema details, screens, and seat layouts.',
            'breadcrumb' => 'Cinema',
            'icon' => 'armchair',
        ]);
    }
}
