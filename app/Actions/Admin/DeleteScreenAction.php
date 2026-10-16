<?php

namespace App\Actions\Admin;

use App\Models\Screen;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteScreenAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Screen $screen): void
    {
        DB::transaction(function () use ($screen): void {
            $screen = Screen::query()->lockForUpdate()->findOrFail($screen->id);
            if ($screen->showtimes()->where('starts_at', '>', now())->exists()) {
                throw ValidationException::withMessages(['screen' => 'A screen with future showtimes cannot be deleted.']);
            }
            if ($screen->showtimes()->exists()) {
                throw ValidationException::withMessages(['screen' => 'A screen with showtime history cannot be deleted.']);
            }
            if ($screen->seats()->whereHas('bookingItems')->exists() || $screen->seats()->whereHas('holds')->exists()) {
                throw ValidationException::withMessages(['screen' => 'A screen with booking history or seat holds cannot be deleted.']);
            }

            $before = $this->audit->snapshot($screen);
            $screen->seats()->delete();
            $screen->delete();
            $this->audit->log('screen.deleted', $screen, $before, null);
        });
    }
}
