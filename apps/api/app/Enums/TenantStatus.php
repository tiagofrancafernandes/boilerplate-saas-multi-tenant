<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELED = 'canceled';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            static::TRIALING => 'Trialing',
            static::ACTIVE => 'Active',
            static::PAST_DUE => 'Past Due',
            static::CANCELED => 'Canceled',
            static::SUSPENDED => 'Suspended',
        };
    }
}
