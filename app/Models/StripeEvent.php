<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['processed_at' => 'immutable_datetime'];
    }
}
