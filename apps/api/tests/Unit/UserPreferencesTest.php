<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function testUserPreferencesHappyPathReturnsDefaultsWhenColumnIsNull(): void
    {
        $user = User::factory()->create([
            'preferences' => null,
        ]);

        $preferences = $user->getPreferencesWithDefaults();

        $this->assertInstanceOf(Collection::class, $preferences);
        $this->assertNull($preferences->get('locale'));
        $this->assertSame('UTC', $preferences->get('timezone'));
        $this->assertNull($preferences->get('color_scheme'));
    }

    public function testUserPreferencesHappyPathMergesCustomValuesWithDefaults(): void
    {
        $user = User::factory()->create([
            'preferences' => [
                'locale' => 'pt_BR',
                'color_scheme' => 'dark',
            ],
        ]);

        $preferences = $user->getPreferencesWithDefaults();

        $this->assertSame('pt_BR', $preferences->get('locale'));
        $this->assertSame('dark', $preferences->get('color_scheme'));
        $this->assertSame('UTC', $preferences->get('timezone'));
    }

    public function testUpdatePreferencesHappyPathPersistsAndMergesValues(): void
    {
        $user = User::factory()->create();

        $user->updatePreferences([
            'locale' => 'en_US',
            'timezone' => 'America/New_York',
        ]);

        $user->refresh();
        $preferences = $user->getPreferencesWithDefaults();

        $this->assertSame('en_US', $preferences->get('locale'));
        $this->assertSame('America/New_York', $preferences->get('timezone'));
        $this->assertNull($preferences->get('color_scheme'));

        $user->updatePreferences([
            'color_scheme' => 'light',
        ]);

        $user->refresh();
        $updated = $user->getPreferencesWithDefaults();

        $this->assertSame('en_US', $updated->get('locale'));
        $this->assertSame('America/New_York', $updated->get('timezone'));
        $this->assertSame('light', $updated->get('color_scheme'));
    }

    public function testUserPreferencesHappyPathPreservesFutureCustomKeys(): void
    {
        $user = User::factory()->create([
            'preferences' => [
                'future_feature_flag' => true,
                'items_per_page' => 50,
            ],
        ]);

        $preferences = $user->getPreferencesWithDefaults();

        $this->assertTrue($preferences->get('future_feature_flag'));
        $this->assertSame(50, $preferences->get('items_per_page'));
        $this->assertSame('UTC', $preferences->get('timezone'));
    }
}
