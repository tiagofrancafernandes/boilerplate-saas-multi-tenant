<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Tenant;

if (!class_exists(SubscriptionAccessService::class)) {
    final class SubscriptionAccessService
    {
        public static function canPerformWrite(Tenant $tenant): bool
        {
            return $tenant->canWrite();
        }

        public static function shouldWarnSubscription(Tenant $tenant): bool
        {
            if ($tenant->isPaidActive()) {
                return false;
            }

            if ($tenant->isInTrial()) {
                return false;
            }

            if ($tenant->isInGracePeriod()) {
                return true;
            }

            return false;
        }

        /**
         * @return array{can_write: bool, should_warn: bool, in_trial: bool, in_grace_period: bool, status: string}
         */
        public static function getStatusDetails(Tenant $tenant): array
        {
            return [
                'can_write' => static::canPerformWrite($tenant),
                'should_warn' => static::shouldWarnSubscription($tenant),
                'in_trial' => $tenant->isInTrial(),
                'in_grace_period' => $tenant->isInGracePeriod(),
                'status' => $tenant->status->value,
            ];
        }
    }
}
