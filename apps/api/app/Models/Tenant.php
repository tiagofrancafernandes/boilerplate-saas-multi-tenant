<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'status',
        'plan',
        'paid_until',
        'trial_ends_at',
        'payment_failed_at',
        'data',
    ];

    /**
     * Custom columns to not store in json data.
     *
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'status',
            'plan',
            'paid_until',
            'trial_ends_at',
            'payment_failed_at',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'plan' => SubscriptionPlan::class,
            'paid_until' => 'datetime',
            'trial_ends_at' => 'datetime',
            'payment_failed_at' => 'datetime',
        ];
    }

    public function isInTrial(): bool
    {
        if ($this->trial_ends_at === null) {
            return false;
        }

        return now()->utc()->lessThanOrEqualTo($this->trial_ends_at);
    }

    public function isPaidActive(): bool
    {
        if ($this->paid_until === null) {
            return false;
        }

        return now()->utc()->lessThanOrEqualTo($this->paid_until);
    }

    public function isInGracePeriod(): bool
    {
        if ($this->payment_failed_at === null) {
            return false;
        }

        $graceDays = (int) config('subscription.grace_period.days', 3);
        $gracePeriodEndsAt = $this->payment_failed_at->copy()->addDays($graceDays);

        return now()->utc()->lessThanOrEqualTo($gracePeriodEndsAt);
    }

    public function canWrite(): bool
    {
        if ($this->status === TenantStatus::SUSPENDED) {
            return false;
        }

        if ($this->status === TenantStatus::CANCELED) {
            return false;
        }

        if ($this->isInTrial()) {
            return true;
        }

        if ($this->isPaidActive()) {
            return true;
        }

        if ($this->isInGracePeriod()) {
            return true;
        }

        return false;
    }
}
