<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Settings',
            'Configure the CineBook cinema experience.',
            'System',
            'settings',
            Setting::query()->with('updater'),
            ['key', 'value'],
            ['key', 'updated_at'],
            [
                ['label' => 'Setting', 'key' => 'key'],
                ['label' => 'Value', 'key' => 'value'],
                ['label' => 'Updated by', 'key' => 'updater.name'],
                ['label' => 'Updated', 'key' => 'updated_at'],
            ],
            defaultSort: 'key',
        );
    }
}
