<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Adapters\AsaasPaymentAdapter;
use App\Services\Payment\Adapters\StripePaymentAdapter;
use InvalidArgumentException;

if (!class_exists(PaymentManager::class)) {
    final class PaymentManager
    {
        public static function resolve(?string $gateway = null): PaymentGatewayInterface
        {
            $selectedGateway = $gateway ?? (string) config('subscription.default_gateway', 'stripe');

            if ($selectedGateway === 'stripe') {
                return new StripePaymentAdapter();
            }

            if ($selectedGateway === 'asaas') {
                return new AsaasPaymentAdapter();
            }

            throw new InvalidArgumentException("Unsupported payment gateway: {$selectedGateway}");
        }
    }
}
