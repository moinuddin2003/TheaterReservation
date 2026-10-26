<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Audit Logs',
            'pageSubtitle' => 'Read-only history of important account and system actions.',
            'breadcrumb' => 'System',
            'icon' => 'scroll-text',
        ]);
    }
}
