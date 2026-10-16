<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'used_at' => 'immutable_datetime',
            'voided_at' => 'immutable_datetime',
        ];
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(TicketScan::class);
    }
}
