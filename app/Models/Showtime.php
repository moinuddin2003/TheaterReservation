<?php

namespace App\Models;

use App\Enums\ShowtimeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'movie_id',
    'screen_id',
    'starts_at',
    'ends_at',
    'cancelled_at',
    'cancelled_by',
    'cancel_reason',
])]
class Showtime extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ShowtimeStatus::Scheduled->value,
    ];

    /**
     * @return BelongsTo<Movie, $this>
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * @return BelongsTo<Screen, $this>
     */
    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return HasMany<SeatHold, $this>
     */
    public function seatHolds(): HasMany
    {
        return $this->hasMany(SeatHold::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<BookingItem, $this>
     */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @param  Builder<Showtime>  $query
     * @return Builder<Showtime>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', ShowtimeStatus::Scheduled);
    }

    /**
     * @param  Builder<Showtime>  $query
     * @return Builder<Showtime>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', ShowtimeStatus::Scheduled)
            ->where('starts_at', '>=', now());
    }

    /**
     * @param  Builder<Showtime>  $query
     * @return Builder<Showtime>
     */
    public function scopeOnDate(Builder $query, string|Carbon $date): Builder
    {
        return $query->whereDate('starts_at', $date);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'base_price_cents' => 'integer',
            'status' => ShowtimeStatus::class,
        ];
    }

    public function isScheduled(): bool
    {
        return $this->status === ShowtimeStatus::Scheduled;
    }

    public function isCancelled(): bool
    {
        return $this->status === ShowtimeStatus::Cancelled;
    }

    public function startsInPast(): bool
    {
        return $this->starts_at->isPast();
    }

    public function hasEnded(): bool
    {
        return $this->ends_at->isPast();
    }

    /**
     * The moment sales close for this showtime.
     */
    public function salesCloseAt(): Carbon
    {
        return $this->starts_at->copy()->subMinutes(
            (int) config('cinebook.sales_cutoff_minutes', 15)
        );
    }

    public function hasSalesClosed(): bool
    {
        return now()->greaterThanOrEqualTo($this->salesCloseAt());
    }

    public function isBookable(): bool
    {
        return $this->isScheduled() && ! $this->hasSalesClosed();
    }
}
