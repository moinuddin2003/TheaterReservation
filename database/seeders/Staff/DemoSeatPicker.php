<?php

namespace Database\Seeders\Staff;

use App\Models\Seat;
use Illuminate\Support\Collection;

/**
 * Hands out free seats of one show: side by side in one row, or exact labels.
 */
class DemoSeatPicker
{
    /** @var array<string, bool> seat label => already given out */
    private array $taken = [];

    /**
     * @param  Collection<int, Seat>  $seats  the screen's seats, ordered by row then number
     */
    public function __construct(private readonly Collection $seats) {}

    /**
     * @param  list<string>  $labels  exact seats to give out; empty = the next free run
     * @return Collection<int, Seat>
     */
    public function take(int $count, array $labels = []): Collection
    {
        $picked = $labels !== []
            ? $this->seats->whereIn('label', $labels)->values()
            : $this->nextRun($count);

        foreach ($picked as $seat) {
            $this->taken[$seat->label] = true;
        }

        return $picked;
    }

    /**
     * @return Collection<int, Seat>
     */
    private function nextRun(int $count): Collection
    {
        foreach ($this->seats->groupBy('row_label') as $row) {
            $free = $row->reject(fn (Seat $seat): bool => isset($this->taken[$seat->label]))->values();

            for ($start = 0; $start + $count <= $free->count(); $start++) {
                $run = $free->slice($start, $count)->values();

                if ($run->last()->seat_number - $run->first()->seat_number === $count - 1) {
                    return $run;
                }
            }
        }

        throw new \RuntimeException('No free run of '.$count.' seats left on this screen.');
    }
}
