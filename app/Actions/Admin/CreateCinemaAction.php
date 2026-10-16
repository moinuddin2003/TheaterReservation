<?php

namespace App\Actions\Admin;

use App\Models\Cinema;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CreateCinemaAction
{
    public function __construct(private AuditLogger $audit) {}

    /** @param array{name: string, address?: ?string, phone?: ?string} $data */
    public function execute(array $data): Cinema
    {
        return DB::transaction(function () use ($data): Cinema {
            $cinema = Cinema::query()->create($data);
            $this->audit->log('cinema.created', $cinema, null, $this->audit->snapshot($cinema));

            return $cinema;
        });
    }
}
