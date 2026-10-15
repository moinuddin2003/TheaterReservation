<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Movies',
            'pageSubtitle' => 'Manage the films available in your cinema.',
            'breadcrumb' => 'Catalog',
            'icon' => 'clapperboard',
        ]);
    }
}
