<?php

namespace App\Models;

use App\Enums\SeatType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seat extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['type' => SeatType::class, 'is_disabled' => 'boolean'];
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }
}
