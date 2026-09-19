<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonInterface;

final class SubscriptionResultDTO
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly bool $isSuccess,
        public readonly ?CarbonInterface $paidUntil = null,
        public readonly ?string $errorMessage = null,
    ) {
    }
}
