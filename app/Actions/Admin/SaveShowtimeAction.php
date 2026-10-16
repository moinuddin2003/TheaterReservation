<?php

namespace App\Actions\Admin;

use App\Enums\ShowtimeStatus;
use App\Models\Movie;
use App\Models\Showtime;
use App\Services\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveShowtimeAction
{
    public function __construct(private AuditLogger $audit) {}

    /** @param array{movie_id: int, screen_id: int, starts_at: string, base_price_cents: int} $data */
    public function execute(array $data, ?Showtime $showtime = null): Showtime
    {
        return DB::transaction(function () use ($data, $showtime): Showtime {
            $showtime = $showtime ? Showtime::query()->lockForUpdate()->findOrFail($showtime->id) : new Showtime;
            $movie = Movie::query()->findOrFail($data['movie_id']);
            $start = Carbon::parse($data['starts_at']);
            $end = $start->copy()->addMinutes((int) $movie->duration_minutes + 20);

            if ($showtime->status === ShowtimeStatus::Cancelled) {
                throw ValidationException::withMessages(['showtime' => 'Cancelled showtimes cannot be edited.']);
            }
            $overlaps = Showtime::query()
                ->where('screen_id', $data['screen_id'])
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->when($showtime->exists, fn ($query) => $query->whereKeyNot($showtime->id))
                ->exists();
            if ($overlaps) {
                throw ValidationException::withMessages(['starts_at' => 'This screening overlaps another showtime on the selected screen.']);
            }

            $before = $showtime->exists ? $this->audit->snapshot($showtime) : null;
            $showtime->fill([
                'movie_id' => $data['movie_id'],
                'screen_id' => $data['screen_id'],
                'starts_at' => $start,
                'ends_at' => $end,
                'base_price_cents' => $data['base_price_cents'],
                'status' => ShowtimeStatus::Scheduled,
            ])->save();
            $this->audit->log($before === null ? 'showtime.created' : 'showtime.updated', $showtime, $before, $this->audit->snapshot($showtime));

            return $showtime->refresh();
        });
    }
}
