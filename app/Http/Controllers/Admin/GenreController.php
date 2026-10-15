<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Genres',
            'pageSubtitle' => 'Organize movies into browsable genres.',
            'breadcrumb' => 'Catalog',
            'icon' => 'tags',
        ]);
    }
}
