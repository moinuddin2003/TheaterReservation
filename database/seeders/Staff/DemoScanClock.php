<?php

namespace Database\Seeders\Staff;

use App\Models\Showtime;
use Carbon\CarbonInterface;

/**
 * Gives each demo admission its own time, starting 55 minutes before the show, two minutes apart.
 */
class DemoScanClock
{
    /** @var array<int, int> show id => admissions handed out */
    private array $count = [];

    public function next(Showtime $show): CarbonInterface
    {
        $position = $this->count[$show->id] ?? 0;
        $this->count[$show->id] = $position + 1;

        return $show->starts_at->copy()->subMinutes(55)->addMinutes(2 * $position);
    }
}
