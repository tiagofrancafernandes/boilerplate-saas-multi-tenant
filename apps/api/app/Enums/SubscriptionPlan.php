<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionPlan: string
{
    case STARTER = 'starter';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            static::STARTER => 'Starter',
            static::PROFESSIONAL => 'Professional',
            static::ENTERPRISE => 'Enterprise',
        };
    }
}
