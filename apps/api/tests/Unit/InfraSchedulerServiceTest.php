<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Domain\InfraSchedulerService;
use Illuminate\Http\Request;
use Tests\TestCase;

class InfraSchedulerServiceTest extends TestCase
{
    public function testIsEnabledHappyPathReturnsTrueWhenConfigured(): void
    {
        config(['app_rules.infra.scheduler_enabled' => true]);

        $this->assertTrue(InfraSchedulerService::isEnabled());
    }

    public function testIsEnabledSadPathReturnsFalseWhenDisabled(): void
    {
        config(['app_rules.infra.scheduler_enabled' => false]);

        $this->assertFalse(InfraSchedulerService::isEnabled());
    }

    public function testIsAuthorizedHappyPathMatchesConfiguredStaticHeader(): void
    {
        config([
            'app_rules.infra.scheduler_auth_token' => 'my-secret-key-99',
            'app_rules.infra.scheduler_header_name' => 'X-Infra-Key',
        ]);

        $request = Request::create('/api/infra/scheduler', 'POST');
        $request->headers->set('X-Infra-Key', 'my-secret-key-99');

        $this->assertTrue(InfraSchedulerService::isAuthorized($request));
    }

    public function testIsAuthorizedSadPathReturnsFalseForMismatchedToken(): void
    {
        config([
            'app_rules.infra.scheduler_auth_token' => 'my-secret-key-99',
            'app_rules.infra.scheduler_header_name' => 'X-Infra-Key',
        ]);

        $request = Request::create('/api/infra/scheduler', 'POST');
        $request->headers->set('X-Infra-Key', 'wrong-key');

        $this->assertFalse(InfraSchedulerService::isAuthorized($request));
    }

    public function testIsAuthorizedSadPathReturnsFalseWhenTokenEmpty(): void
    {
        config([
            'app_rules.infra.scheduler_auth_token' => null,
            'app_rules.infra.scheduler_header_name' => 'X-Infra-Key',
        ]);

        $request = Request::create('/api/infra/scheduler', 'POST');
        $request->headers->set('X-Infra-Key', 'some-token');

        $this->assertFalse(InfraSchedulerService::isAuthorized($request));
    }

    public function testRunSchedulerHappyPathReturnsStandardResultArray(): void
    {
        $result = InfraSchedulerService::runScheduler();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('exit_code', $result);
        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('executed_at', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsInt($result['exit_code']);
        $this->assertIsString($result['output']);
        $this->assertIsString($result['executed_at']);
    }
}
