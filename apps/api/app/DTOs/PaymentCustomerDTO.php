<?php

declare(strict_types=1);

namespace App\DTOs;

final class PaymentCustomerDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
    ) {
    }
}
