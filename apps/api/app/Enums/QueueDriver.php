<?php

declare(strict_types=1);

namespace App\Enums;

enum QueueDriver: string
{
    case LOCAL = 'local';
    case QSTASH = 'qstash';
    case REDIS = 'redis';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            static::LOCAL => 'Local Internal Bypass',
            static::QSTASH => 'Upstash QStash Push',
            static::REDIS => 'Redis Daemon Worker',
            static::NONE => 'Disabled',
        };
    }
}
