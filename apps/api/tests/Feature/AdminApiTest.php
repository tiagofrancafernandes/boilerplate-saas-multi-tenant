<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminTenantsSadPathReturns401WhenUnauthenticated(): void
    {
        $response = $this->getJson('/api/admin/tenants');

        $response->assertStatus(401);
    }

    public function testAdminTenantsHappyPathReturnsTenantListWhenAuthenticated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/tenants');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tenants',
        ]);
    }
}
