<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Models\Plan;
use Tests\TestCase;

class PlanModelTest extends TestCase
{
    public function testPlanModelHappyPathFormattedPriceAndCasts(): void
    {
        $plan = new Plan([
            'slug' => SubscriptionPlan::STARTER,
            'name' => 'Starter Plan',
            'description' => 'Test Starter',
            'price_cents' => 4900,
            'currency' => 'BRL',
            'billing_interval' => 'month',
            'trial_days' => 14,
            'is_active' => true,
            'features' => ['Feature 1', 'Feature 2'],
        ]);

        $this->assertSame(SubscriptionPlan::STARTER, $plan->slug);
        $this->assertSame('R$ 49,00', $plan->formattedPrice());
        $this->assertTrue($plan->is_active);
        $this->assertSame(14, $plan->trial_days);
        $this->assertCount(2, $plan->features);
    }

    public function testPlanModelHappyPathCurrencies(): void
    {
        $usdPlan = new Plan(['price_cents' => 1999, 'currency' => 'USD']);
        $eurPlan = new Plan(['price_cents' => 2450, 'currency' => 'EUR']);

        $this->assertSame('$ 19,99', $usdPlan->formattedPrice());
        $this->assertSame('€ 24,50', $eurPlan->formattedPrice());
    }
}
