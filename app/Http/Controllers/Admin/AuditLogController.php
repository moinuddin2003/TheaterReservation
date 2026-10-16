<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'actor' => ['nullable', 'integer', 'exists:users,id'],
            'entity' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:60'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(['created_at', 'action', 'auditable_type'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $logs = AuditLog::query()->with('user')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('action', 'like', '%'.$search.'%')->orWhere('auditable_type', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$search.'%'))))
            ->when($filters['actor'] ?? null, fn ($query, $actor) => $query->where('user_id', $actor))
            ->when($filters['entity'] ?? null, fn ($query, $entity) => $query->where('auditable_type', $entity))
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate(20)->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actors' => User::query()->whereHas('auditLogs')->orderBy('name')->get(['id', 'name']),
            'entities' => AuditLog::query()->distinct()->orderBy('auditable_type')->pluck('auditable_type'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'timezone' => $this->timezone(),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', ['log' => $auditLog, 'timezone' => $this->timezone()]);
    }

    private function timezone(): string
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
    }
}
