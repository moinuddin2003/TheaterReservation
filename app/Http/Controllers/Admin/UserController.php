<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Customers',
            'pageSubtitle' => 'View customer accounts.',
            'breadcrumb' => 'People',
            'icon' => 'users',
        ]);
    }
}
