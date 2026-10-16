<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CinemaController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Cinema & Screens',
            'Manage cinema details, screens, and seat layouts.',
            'Cinema',
            'armchair',
            Cinema::query()->with('screens'),
            ['name', 'address', 'phone'],
            ['name', 'created_at'],
            [
                ['label' => 'Cinema', 'key' => 'name'],
                ['label' => 'Address', 'key' => 'address'],
                ['label' => 'Phone', 'key' => 'phone'],
                ['label' => 'Screens', 'key' => 'screens', 'property' => 'name'],
            ],
        );
    }
}
