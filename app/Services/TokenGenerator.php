<?php

namespace App\Services;

use Illuminate\Support\Str;

class TokenGenerator
{
    public function generate(): string
    {
        return Str::lower(Str::random(40));
    }
}
