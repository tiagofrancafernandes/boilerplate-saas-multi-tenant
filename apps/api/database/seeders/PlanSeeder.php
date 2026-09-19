<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SubscriptionPlan;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => SubscriptionPlan::STARTER,
                'name' => 'Starter',
                'description' => 'Essential features for individuals and small teams beginning their journey.',
                'price_cents' => 4900,
                'currency' => 'BRL',
                'billing_interval' => 'month',
                'trial_days' => 14,
                'is_active' => true,
                'features' => [
                    'Up to 5 team members',
                    '10GB Cloud Storage',
                    'Standard Support (Email)',
                    'Basic Analytics Dashboard',
                ],
            ],
            [
                'slug' => SubscriptionPlan::PROFESSIONAL,
                'name' => 'Professional',
                'description' => 'Advanced power and collaboration tools designed for growing businesses.',
                'price_cents' => 9900,
                'currency' => 'BRL',
                'billing_interval' => 'month',
                'trial_days' => 14,
                'is_active' => true,
                'features' => [
                    'Up to 25 team members',
                    '100GB Cloud Storage',
                    'Priority 24/7 Support',
                    'Advanced Analytics & Reporting',
                    'Custom Domain Integration',
                    'API & Webhooks Access',
                ],
            ],
            [
                'slug' => SubscriptionPlan::ENTERPRISE,
                'name' => 'Enterprise',
                'description' => 'Maximum scale, dedicated resources, and enterprise-grade security.',
                'price_cents' => 29900,
                'currency' => 'BRL',
                'billing_interval' => 'month',
                'trial_days' => 14,
                'is_active' => true,
                'features' => [
                    'Unlimited team members',
                    '1TB Cloud Storage',
                    'Dedicated Account Manager',
                    'Enterprise SLA (99.9% uptime)',
                    'SSO & SAML Authentication',
                    'Audit Logs & Compliance Reports',
                    'Custom Integrations & Dedicated IP',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData,
            );
        }
    }
}
