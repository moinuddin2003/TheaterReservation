<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Audit Logs',
            'Read-only history of important account and system actions.',
            'System',
            'scroll-text',
            AuditLog::query()->with('user'),
            ['action', 'auditable_type', 'user.name'],
            ['created_at', 'action', 'auditable_type'],
            [
                ['label' => 'Action', 'key' => 'action'],
                ['label' => 'Entity', 'key' => 'auditable_type'],
                ['label' => 'Entity ID', 'key' => 'auditable_id'],
                ['label' => 'Actor', 'key' => 'user.name'],
                ['label' => 'Timestamp', 'key' => 'created_at'],
            ],
        );
    }
}
