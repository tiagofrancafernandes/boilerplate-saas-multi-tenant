<?php

declare(strict_types=1);

namespace App\Services\Payment\Adapters;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentCustomerDTO;
use App\DTOs\SubscriptionResultDTO;
use App\Models\Tenant;

if (!class_exists(AsaasPaymentAdapter::class)) {
    final class AsaasPaymentAdapter implements PaymentGatewayInterface
    {
        public function getGatewayName(): string
        {
            return 'asaas';
        }

        public function createCustomer(Tenant $tenant, string $name, string $email): PaymentCustomerDTO
        {
            $customerId = 'cus_asaas_' . $tenant->id;

            return new PaymentCustomerDTO(
                id: $customerId,
                name: $name,
                email: $email,
            );
        }

        public function createSubscription(
            Tenant $tenant,
            string $planIdentifier,
            string $paymentMethodToken,
        ): SubscriptionResultDTO {
            if ($paymentMethodToken === '') {
                return new SubscriptionResultDTO(
                    subscriptionId: '',
                    isSuccess: false,
                    errorMessage: 'Invalid payment method token',
                );
            }

            $paidUntil = now()->utc()->addMonth();

            return new SubscriptionResultDTO(
                subscriptionId: 'sub_asaas_' . $tenant->id,
                isSuccess: true,
                paidUntil: $paidUntil,
            );
        }

        public function cancelSubscription(string $subscriptionId): bool
        {
            if ($subscriptionId === '') {
                return false;
            }

            return true;
        }
    }
}
