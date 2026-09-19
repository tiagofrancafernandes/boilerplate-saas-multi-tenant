<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function testGetPreferencesHappyPathReturnsDefaultPreferences(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user/preferences');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'locale' => null,
                'timezone' => 'UTC',
                'color_scheme' => null,
            ],
        ]);
    }

    public function testUpdatePreferencesHappyPathUpdatesAndReturnsMergedData(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/preferences', [
            'locale' => 'pt_BR',
            'timezone' => 'America/Sao_Paulo',
            'color_scheme' => 'dark',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Preferences updated successfully.',
            'data' => [
                'locale' => 'pt_BR',
                'timezone' => 'America/Sao_Paulo',
                'color_scheme' => 'dark',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    public function testGetPreferencesSadPathReturns401WhenUnauthenticated(): void
    {
        $response = $this->getJson('/api/user/preferences');

        $response->assertStatus(401);
    }

    public function testUpdatePreferencesSadPathReturns401WhenUnauthenticated(): void
    {
        $response = $this->putJson('/api/user/preferences', [
            'color_scheme' => 'dark',
        ]);

        $response->assertStatus(401);
    }

    public function testUpdatePreferencesSadPathReturns422ForInvalidTimezone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/preferences', [
            'timezone' => 'Invalid/Non_Existent_Timezone',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['timezone']);
    }

    public function testUpdatePreferencesSadPathReturns422ForInvalidColorScheme(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/preferences', [
            'color_scheme' => 'neon_green_invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['color_scheme']);
    }
}
