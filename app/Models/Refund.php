<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id',
    'booking_id',
    'currency',
    'reason',
    'gateway_refund_id',
    'idempotency_key',
    'failure_message',
    'initiated_by',
    'processed_at',
])]
class Refund extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => RefundStatus::Pending->value,
        'currency' => 'USD',
    ];

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * @param  Builder<Refund>  $query
     * @return Builder<Refund>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', RefundStatus::Pending);
    }

    /**
     * @param  Builder<Refund>  $query
     * @return Builder<Refund>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', RefundStatus::Failed);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount_cents' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === RefundStatus::Pending;
    }

    public function isSucceeded(): bool
    {
        return $this->status === RefundStatus::Succeeded;
    }

    public function isFailed(): bool
    {
        return $this->status === RefundStatus::Failed;
    }
}
