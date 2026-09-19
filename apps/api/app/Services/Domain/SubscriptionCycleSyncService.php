<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Support\Collection;

if (!class_exists(SubscriptionCycleSyncService::class)) {
    final class SubscriptionCycleSyncService
    {
        /**
         * Scan all tenants and synchronize their subscription cycles.
         *
         * @return array{
         *     total_scanned: int,
         *     active: int,
         *     past_due: int,
         *     suspended: int,
         *     unchanged: int,
         *     changed: int,
         *     details: list<array{
         *         tenant_id: string,
         *         previous_status: string,
         *         current_status: string,
         *         payment_failed_at: ?string,
         *         changed: bool,
         *         reason: string
         *     }>
         * }
         */
        public static function syncAll(): array
        {
            $summary = [
                'total_scanned' => 0,
                'active' => 0,
                'past_due' => 0,
                'suspended' => 0,
                'unchanged' => 0,
                'changed' => 0,
                'details' => [],
            ];

            Tenant::query()->chunk(100, function (Collection $tenants) use (&$summary): void {
                /** @var Tenant $tenant */
                foreach ($tenants as $tenant) {
                    $result = static::syncTenant($tenant);

                    $summary['total_scanned']++;
                    $summary['details'][] = $result;

                    static::accumulateStatusSummary($summary, $result);
                }
            });

            return $summary;
        }

        /**
         * Synchronize a single tenant subscription lifecycle.
         *
         * @return array{
         *     tenant_id: string,
         *     previous_status: string,
         *     current_status: string,
         *     payment_failed_at: ?string,
         *     changed: bool,
         *     reason: string
         * }
         */
        public static function syncTenant(Tenant $tenant): array
        {
            $previousStatus = $tenant->status;

            if ($tenant->status === TenantStatus::CANCELED) {
                return static::formatResult($tenant, $previousStatus, false, 'Tenant subscription is explicitly canceled.');
            }

            if ($tenant->isInTrial()) {
                return static::resolveActiveTrial($tenant, $previousStatus);
            }

            if ($tenant->isPaidActive()) {
                return static::resolvePaidActive($tenant, $previousStatus);
            }

            return static::resolveLapsedOrPending($tenant, $previousStatus);
        }

        /**
         * @param array<string, mixed> $summary
         * @param array{current_status: string, changed: bool} $result
         */
        private static function accumulateStatusSummary(array &$summary, array $result): void
        {
            if ($result['changed']) {
                $summary['changed']++;
            }

            if (!$result['changed']) {
                $summary['unchanged']++;
            }

            match ($result['current_status']) {
                TenantStatus::ACTIVE->value, TenantStatus::TRIALING->value => $summary['active']++,
                TenantStatus::PAST_DUE->value => $summary['past_due']++,
                TenantStatus::SUSPENDED->value => $summary['suspended']++,
                default => null,
            };
        }

        /**
         * Handle active trial state.
         *
         * @return array{
         *     tenant_id: string,
         *     previous_status: string,
         *     current_status: string,
         *     payment_failed_at: ?string,
         *     changed: bool,
         *     reason: string
         * }
         */
        private static function resolveActiveTrial(Tenant $tenant, TenantStatus $previousStatus): array
        {
            $changed = false;

            if ($tenant->payment_failed_at !== null) {
                $tenant->payment_failed_at = null;
                $changed = true;
            }

            if ($tenant->status === TenantStatus::PAST_DUE || $tenant->status === TenantStatus::SUSPENDED) {
                $tenant->status = TenantStatus::TRIALING;
                $changed = true;
            }

            if ($changed) {
                $tenant->save();
            }

            return static::formatResult(
                $tenant,
                $previousStatus,
                $changed,
                'Tenant is within active trial period.'
            );
        }

        /**
         * Handle active paid subscription state.
         *
         * @return array{
         *     tenant_id: string,
         *     previous_status: string,
         *     current_status: string,
         *     payment_failed_at: ?string,
         *     changed: bool,
         *     reason: string
         * }
         */
        private static function resolvePaidActive(Tenant $tenant, TenantStatus $previousStatus): array
        {
            $changed = false;

            if ($tenant->payment_failed_at !== null) {
                $tenant->payment_failed_at = null;
                $changed = true;
            }

            if ($tenant->status !== TenantStatus::ACTIVE) {
                $tenant->status = TenantStatus::ACTIVE;
                $changed = true;
            }

            if ($changed) {
                $tenant->save();
            }

            return static::formatResult(
                $tenant,
                $previousStatus,
                $changed,
                'Tenant has an active paid subscription.'
            );
        }

        /**
         * Handle lapsed trial or unpaid period (grace period or suspension).
         *
         * @return array{
         *     tenant_id: string,
         *     previous_status: string,
         *     current_status: string,
         *     payment_failed_at: ?string,
         *     changed: bool,
         *     reason: string
         * }
         */
        private static function resolveLapsedOrPending(Tenant $tenant, TenantStatus $previousStatus): array
        {
            $changed = false;

            if ($tenant->payment_failed_at === null) {
                $tenant->payment_failed_at = $tenant->paid_until ?? $tenant->trial_ends_at ?? now()->utc();
                $changed = true;
            }

            $inGracePeriod = $tenant->isInGracePeriod();

            if ($inGracePeriod) {
                return static::applyPastDueGracePeriod($tenant, $previousStatus, $changed);
            }

            return static::applySuspendedLapsed($tenant, $previousStatus, $changed);
        }

        /**
         * @return array{
         *     tenant_id: string,
         *     previous_status: string,
         *     current_status: string,
         *     payment_failed_at: ?string,
         *     changed: bool,
         *     reason: string
         * }
         */
        private static function applyPastDueGracePeriod(Tenant $tenant, TenantStatus $previousStatus, bool $initialChanged): array
        {
            $changed = $initialChanged;

            if ($tenant->status !== TenantStatus::PAST_DUE) {
                $tenant->status = TenantStatus::PAST_DUE;
                $changed = true;
            }

            if ($changed) {
                $tenant->save();
            }

            return static::formatResult(
                $tenant,
                $previousStatus,
                $changed,
                'Subscription payment is pending or past-due, currently within grace period.'
            );
        }

        /**
         * @return array{
         *     tenant_id: string,
         *     previous_status: string,
         *     current_status: string,
         *     payment_failed_at: ?string,
         *     changed: bool,
         *     reason: string
         * }
         */
        private static function applySuspendedLapsed(Tenant $tenant, TenantStatus $previousStatus, bool $initialChanged): array
        {
            $changed = $initialChanged;

            if ($tenant->status !== TenantStatus::SUSPENDED) {
                $tenant->status = TenantStatus::SUSPENDED;
                $changed = true;
            }

            if ($changed) {
                $tenant->save();
            }

            return static::formatResult(
                $tenant,
                $previousStatus,
                $changed,
                'Subscription has expired and exceeded grace period. Tenant access suspended.'
            );
        }

        /**
         * @return array{
         *     tenant_id: string,
         *     previous_status: string,
         *     current_status: string,
         *     payment_failed_at: ?string,
         *     changed: bool,
         *     reason: string
         * }
         */
        private static function formatResult(
            Tenant $tenant,
            TenantStatus $previousStatus,
            bool $changed,
            string $reason
        ): array {
            return [
                'tenant_id' => (string) $tenant->id,
                'previous_status' => $previousStatus->value,
                'current_status' => $tenant->status->value,
                'payment_failed_at' => $tenant->payment_failed_at?->toIso8601String(),
                'changed' => $changed,
                'reason' => $reason,
            ];
        }
    }
}
