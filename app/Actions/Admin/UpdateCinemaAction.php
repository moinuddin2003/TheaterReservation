<?php

namespace App\Actions\Admin;

use App\Models\Cinema;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateCinemaAction
{
    public function __construct(private AuditLogger $audit) {}

    /** @param array{name: string, address?: ?string, phone?: ?string} $data */
    public function execute(Cinema $cinema, array $data): Cinema
    {
        return DB::transaction(function () use ($cinema, $data): Cinema {
            $cinema = Cinema::query()->lockForUpdate()->findOrFail($cinema->id);
            $before = $this->audit->snapshot($cinema);
            $cinema->fill($data)->save();
            $this->audit->log('cinema.updated', $cinema, $before, $this->audit->snapshot($cinema));

            return $cinema->refresh();
        });
    }
}
