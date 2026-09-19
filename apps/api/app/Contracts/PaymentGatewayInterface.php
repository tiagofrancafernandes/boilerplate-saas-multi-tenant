<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\PaymentCustomerDTO;
use App\DTOs\SubscriptionResultDTO;
use App\Models\Tenant;

interface PaymentGatewayInterface
{
    public function getGatewayName(): string;

    public function createCustomer(Tenant $tenant, string $name, string $email): PaymentCustomerDTO;

    public function createSubscription(Tenant $tenant, string $planIdentifier, string $paymentMethodToken): SubscriptionResultDTO;

    public function cancelSubscription(string $subscriptionId): bool;
}
