<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('price_cents')->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->string('billing_interval', 20)->default('month');
            $table->smallInteger('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->jsonb('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
