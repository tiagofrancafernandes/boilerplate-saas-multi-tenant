<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    /**
     * Standard system default user preferences.
     *
     * @var array<string, mixed>
     */
    public const DEFAULT_PREFERENCES = [
        'locale' => null,
        'timezone' => 'UTC',
        'color_scheme' => null,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => \Illuminate\Database\Eloquent\Casts\AsCollection::class,
        ];
    }

    /**
     * Resolves user preferences merged with system defaults.
     *
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    public function getPreferencesWithDefaults(): \Illuminate\Support\Collection
    {
        /** @var \Illuminate\Support\Collection<string, mixed>|null $current */
        $current = $this->preferences;

        if ($current === null) {
            return collect(static::DEFAULT_PREFERENCES);
        }

        return collect(static::DEFAULT_PREFERENCES)->merge($current);
    }

    /**
     * Merges and saves new preferences into the user profile.
     *
     * @param  array<string, mixed>  $newPreferences
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    public function updatePreferences(array $newPreferences): \Illuminate\Support\Collection
    {
        $merged = $this->getPreferencesWithDefaults()->merge($newPreferences);
        $this->preferences = $merged;
        $this->save();

        return $this->getPreferencesWithDefaults();
    }

    /**
     * Attribute accessor for resolved preferences.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<\Illuminate\Support\Collection<string, mixed>, never>
     */
    protected function resolvedPreferences(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (): \Illuminate\Support\Collection => $this->getPreferencesWithDefaults(),
        );
    }
}
