<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = trim((string) $request->query('action', ''));
        $actor = trim((string) $request->query('actor', ''));
        $target = trim((string) $request->query('target', ''));
        $date = $request->date('date');
        $selectedLog = $request->integer('log');
        $logs = AuditLog::query()
            ->with('user')
            ->when($action !== '', fn ($query) => $query->where('action', 'like', "%{$action}%"))
            ->when($actor !== '', fn ($query) => $query->whereHas('user', fn ($users) => $users
                ->where('name', 'like', "%{$actor}%")
                ->orWhere('email', 'like', "%{$actor}%")))
            ->when($target !== '', fn ($query) => $query->where(function ($query) use ($target): void {
                $query->where('auditable_type', 'like', "%{$target}%")
                    ->orWhere('auditable_id', is_numeric($target) ? (int) $target : -1)
                    ->orWhereJsonContains('before->key', $target)
                    ->orWhereJsonContains('after->key', $target);
            }))
            ->when($date, fn ($query) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'action' => $action,
            'actor' => $actor,
            'target' => $target,
            'date' => $date?->toDateString(),
            'selectedLog' => $selectedLog ? AuditLog::query()->with('user')->findOrFail($selectedLog) : null,
        ]);
    }
}
