<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PublicApiTest extends TestCase
{
    public function testHealthEndpointHappyPathReturnsOkStatus(): void
    {
        $response = $this->getJson('/api/public/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'service' => 'public-api',
        ]);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'service',
        ]);
    }

    public function testPublicRouteSadPathReturns404ForUnknownEndpoint(): void
    {
        $response = $this->getJson('/api/public/unknown-endpoint');

        $response->assertStatus(404);
    }

    public function testPublicRouteSadPathReturns405ForDisallowedMethod(): void
    {
        $response = $this->postJson('/api/public/health');

        $response->assertStatus(405);
    }
}
