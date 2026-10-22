<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function log(
        User $actor,
        string $action,
        Model $auditable,
        array $before = [],
        array $after = [],
        ?string $ipAddress = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => $ipAddress,
        ]);
    }
}
