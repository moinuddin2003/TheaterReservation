<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'booking_item_id',
    'booking_id',
    'showtime_id',
    'ticket_number',
    'token',
    'used_at',
    'used_by',
    'voided_at',
])]
class Ticket extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TicketStatus::Valid->value,
    ];

    /**
     * @return BelongsTo<BookingItem, $this>
     */
    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Showtime, $this>
     */
    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /**
     * @return HasMany<TicketScan, $this>
     */
    public function scans(): HasMany
    {
        return $this->hasMany(TicketScan::class);
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Valid);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'used_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        return $this->status === TicketStatus::Valid;
    }

    public function isUsed(): bool
    {
        return $this->status === TicketStatus::Used;
    }

    public function isVoid(): bool
    {
        return in_array($this->status, [TicketStatus::Void, TicketStatus::Voided], true);
    }
}
