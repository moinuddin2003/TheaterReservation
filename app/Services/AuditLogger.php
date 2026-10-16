<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function log(string $action, Model $entity, ?array $before = null, ?array $after = null): AuditLog
    {
        $before = $before === null ? null : $this->safeAttributes($before);
        $after = $after === null ? null : $this->safeAttributes($after);

        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => class_basename($entity),
            'auditable_id' => (int) $entity->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function snapshot(Model $entity): array
    {
        return $this->safeAttributes($entity->getAttributes());
    }

    private function safeAttributes(array $attributes): array
    {
        unset($attributes['password'], $attributes['remember_token'], $attributes['token']);

        return $attributes;
    }
}
