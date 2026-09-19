<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\Payment\Adapters\AsaasPaymentAdapter;
use App\Services\Payment\Adapters\StripePaymentAdapter;
use App\Services\Payment\PaymentManager;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function testPaymentManagerHappyPathResolvesSupportedGateways(): void
    {
        $stripe = PaymentManager::resolve('stripe');
        $asaas = PaymentManager::resolve('asaas');

        $this->assertInstanceOf(StripePaymentAdapter::class, $stripe);
        $this->assertSame('stripe', $stripe->getGatewayName());
        $this->assertInstanceOf(AsaasPaymentAdapter::class, $asaas);
        $this->assertSame('asaas', $asaas->getGatewayName());
    }

    public function testPaymentManagerSadPathThrowsExceptionForUnsupportedGateway(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment gateway: unknown_gateway');

        PaymentManager::resolve('unknown_gateway');
    }

    public function testStripeAdapterHappyPathCreatesCustomerAndSubscription(): void
    {
        $tenant = new Tenant(['id' => 'tenant-stripe-test']);
        $adapter = new StripePaymentAdapter();

        $customer = $adapter->createCustomer($tenant, 'John Doe', 'john@example.com');
        $this->assertSame('cus_stripe_tenant-stripe-test', $customer->id);
        $this->assertSame('John Doe', $customer->name);
        $this->assertSame('john@example.com', $customer->email);

        $subscription = $adapter->createSubscription($tenant, 'professional', 'pm_card_valid');
        $this->assertTrue($subscription->isSuccess);
        $this->assertSame('sub_stripe_tenant-stripe-test', $subscription->subscriptionId);
        $this->assertNotNull($subscription->paidUntil);
        $this->assertNull($subscription->errorMessage);

        $canceled = $adapter->cancelSubscription('sub_stripe_tenant-stripe-test');
        $this->assertTrue($canceled);
    }

    public function testStripeAdapterSadPathFailsWithEmptyTokenOrEmptyId(): void
    {
        $tenant = new Tenant(['id' => 'tenant-stripe-sad']);
        $adapter = new StripePaymentAdapter();

        $subscription = $adapter->createSubscription($tenant, 'professional', '');
        $this->assertFalse($subscription->isSuccess);
        $this->assertSame('', $subscription->subscriptionId);
        $this->assertSame('Invalid payment method token', $subscription->errorMessage);

        $canceled = $adapter->cancelSubscription('');
        $this->assertFalse($canceled);
    }

    public function testAsaasAdapterHappyPathCreatesCustomerAndSubscription(): void
    {
        $tenant = new Tenant(['id' => 'tenant-asaas-test']);
        $adapter = new AsaasPaymentAdapter();

        $customer = $adapter->createCustomer($tenant, 'Alice Smith', 'alice@example.com');
        $this->assertSame('cus_asaas_tenant-asaas-test', $customer->id);
        $this->assertSame('Alice Smith', $customer->name);
        $this->assertSame('alice@example.com', $customer->email);

        $subscription = $adapter->createSubscription($tenant, 'starter', 'tok_asaas_valid');
        $this->assertTrue($subscription->isSuccess);
        $this->assertSame('sub_asaas_tenant-asaas-test', $subscription->subscriptionId);
        $this->assertNotNull($subscription->paidUntil);
        $this->assertNull($subscription->errorMessage);

        $canceled = $adapter->cancelSubscription('sub_asaas_tenant-asaas-test');
        $this->assertTrue($canceled);
    }

    public function testAsaasAdapterSadPathFailsWithEmptyTokenOrEmptyId(): void
    {
        $tenant = new Tenant(['id' => 'tenant-asaas-sad']);
        $adapter = new AsaasPaymentAdapter();

        $subscription = $adapter->createSubscription($tenant, 'starter', '');
        $this->assertFalse($subscription->isSuccess);
        $this->assertSame('', $subscription->subscriptionId);
        $this->assertSame('Invalid payment method token', $subscription->errorMessage);

        $canceled = $adapter->cancelSubscription('');
        $this->assertFalse($canceled);
    }
}
