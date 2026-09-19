<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_cents',
        'currency',
        'billing_interval',
        'trial_days',
        'is_active',
        'features',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slug' => SubscriptionPlan::class,
            'price_cents' => 'integer',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'features' => 'array',
        ];
    }

    public function formattedPrice(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'EUR' => '€',
            default => 'R$',
        };

        return sprintf('%s %s', $symbol, number_format($this->price_cents / 100, 2, ',', '.'));
    }
}
