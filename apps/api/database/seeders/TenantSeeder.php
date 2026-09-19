<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (!static::shouldSeedDemoUsers()) {
            return;
        }

        $tenants = [
            [
                'id' => 'demo_tenant',
                'name' => 'Acme Demo Corp',
                'status' => TenantStatus::ACTIVE,
                'plan' => SubscriptionPlan::STARTER,
                'trial_ends_at' => now()->addDays(14),
                'paid_until' => null,
                'payment_failed_at' => null,
                'domain' => 'demo.localhost',
                'user' => [
                    'name' => 'Demo Tenant User',
                    'email' => 'demo@mail.com',
                ],
            ],
            [
                'id' => 'paid_tenant',
                'name' => 'Stark Industries',
                'status' => TenantStatus::ACTIVE,
                'plan' => SubscriptionPlan::PROFESSIONAL,
                'trial_ends_at' => now()->subDays(30),
                'paid_until' => now()->addDays(30),
                'payment_failed_at' => null,
                'domain' => 'stark.localhost',
                'user' => [
                    'name' => 'Paid Tenant User',
                    'email' => 'paid@mail.com',
                ],
            ],
            [
                'id' => 'pending_tenant',
                'name' => 'Wayne Enterprises',
                'status' => TenantStatus::PAST_DUE,
                'plan' => SubscriptionPlan::ENTERPRISE,
                'trial_ends_at' => now()->subDays(60),
                'paid_until' => now()->subDay(),
                'payment_failed_at' => now()->subDay(),
                'domain' => 'wayne.localhost',
                'user' => [
                    'name' => 'Pending Tenant User',
                    'email' => 'pending@mail.com',
                ],
            ],
            [
                'id' => 'expired_tenant',
                'name' => 'Cyberdyne Systems',
                'status' => TenantStatus::SUSPENDED,
                'plan' => SubscriptionPlan::STARTER,
                'trial_ends_at' => now()->subDays(90),
                'paid_until' => now()->subDays(10),
                'payment_failed_at' => now()->subDays(10),
                'domain' => 'cyberdyne.localhost',
                'user' => [
                    'name' => 'Expired Tenant User',
                    'email' => 'expired@mail.com',
                ],
            ],
        ];

        foreach ($tenants as $data) {
            $domain = $data['domain'];
            $userData = $data['user'];

            unset($data['domain'], $data['user']);

            /** @var Tenant $tenant */
            $tenant = Tenant::updateOrCreate(
                ['id' => $data['id']],
                $data,
            );

            $tenant->domains()->updateOrCreate(
                ['domain' => $domain],
                ['domain' => $domain],
            );

            /** @var User $user */
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('power@123'),
                    'email_verified_at' => now(),
                    'preferences' => [
                        'locale' => 'pt_BR',
                        'timezone' => 'UTC',
                        'color_scheme' => null,
                    ],
                ],
            );

            $user->syncRoles(['tenant-admin']);
        }
    }

    public static function shouldSeedDemoUsers(): bool
    {
        if (config('app.env') !== 'production') {
            return true;
        }

        return (bool) config('app_rules.seed_demo_users', false);
    }
}
