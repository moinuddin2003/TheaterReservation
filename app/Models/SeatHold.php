<?php

namespace App\Models;

use App\Enums\HoldStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hold_token',
    'showtime_id',
    'seat_id',
    'user_id',
    'booking_id',
    'expires_at',
    'released_at',
])]
class SeatHold extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => HoldStatus::Active->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hold_token' => 'string',
            'price_cents' => 'integer',
            'status' => HoldStatus::class,
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Showtime, $this>
     */
    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    /**
     * @return BelongsTo<Seat, $this>
     */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @param  Builder<SeatHold>  $query
     * @return Builder<SeatHold>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', HoldStatus::Active);
    }

    /**
     * Active holds whose expiry time has already passed.
     *
     * @param  Builder<SeatHold>  $query
     * @return Builder<SeatHold>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', HoldStatus::Active)
            ->where('expires_at', '<=', now());
    }

    public function isActive(): bool
    {
        return $this->status === HoldStatus::Active;
    }

    /**
     * Whether the hold's expiry moment has passed.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
