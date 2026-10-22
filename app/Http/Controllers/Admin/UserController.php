<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Users',
            'pageSubtitle' => 'View customer and staff accounts.',
            'breadcrumb' => 'People',
            'icon' => 'users',
        ]);
    }
}
