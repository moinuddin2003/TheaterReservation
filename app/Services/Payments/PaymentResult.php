<?php

namespace App\Services\Payments;

final readonly class PaymentResult
{
    public function __construct(
        public bool $successful,
        public ?string $id = null,
        public ?string $url = null,
        public ?string $error = null,
    ) {}
}
