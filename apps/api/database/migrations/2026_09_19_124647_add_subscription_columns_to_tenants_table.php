<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', static function (Blueprint $table): void {
            $table->string('name')->nullable()->after('id');
            $table->string('status', 50)->default('trialing')->index()->after('name');
            $table->string('plan', 50)->nullable()->after('status');
            $table->timestampTz('paid_until')->nullable()->index()->after('plan');
            $table->timestampTz('trial_ends_at')->nullable()->index()->after('paid_until');
            $table->timestampTz('payment_failed_at')->nullable()->after('trial_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', static function (Blueprint $table): void {
            $table->dropColumn([
                'name',
                'status',
                'plan',
                'paid_until',
                'trial_ends_at',
                'payment_failed_at',
            ]);
        });
    }
};
