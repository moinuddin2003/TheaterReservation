<?php

namespace App\Models;

use App\Enums\HoldStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatHold extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => HoldStatus::class,
            'expires_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
        ];
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
