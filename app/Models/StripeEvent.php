<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'stripe_event_id',
    'type',
    'error_message',
    'processed_at',
])]
class StripeEvent extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'received',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isIgnored(): bool
    {
        return $this->status === 'ignored';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
