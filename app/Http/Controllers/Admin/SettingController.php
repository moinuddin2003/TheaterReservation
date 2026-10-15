<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Settings',
            'pageSubtitle' => 'Configure the CineBook cinema experience.',
            'breadcrumb' => 'System',
            'icon' => 'settings',
        ]);
    }
}
