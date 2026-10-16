<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cinema extends Model
{
    protected $guarded = [];

    public function screens(): HasMany
    {
        return $this->hasMany(Screen::class);
    }
}
