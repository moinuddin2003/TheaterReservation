<?php

namespace App\Actions\Admin;

use App\Models\Cinema;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCinemaAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Cinema $cinema): void
    {
        DB::transaction(function () use ($cinema): void {
            $cinema = Cinema::query()->lockForUpdate()->findOrFail($cinema->id);
            if ($cinema->screens()->exists()) {
                throw ValidationException::withMessages(['cinema' => 'This cinema has screens and cannot be deleted. Remove its screens first.']);
            }

            $before = $this->audit->snapshot($cinema);
            $cinema->delete();
            $this->audit->log('cinema.deleted', $cinema, $before, null);
        });
    }
}
