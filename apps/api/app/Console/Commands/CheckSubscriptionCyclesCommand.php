<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Domain\SubscriptionCycleSyncService;
use Illuminate\Console\Command;

class CheckSubscriptionCyclesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-cycles {--tenant= : Check and sync a specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan database tenants and sync subscription statuses (active, past_due, suspended)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        if (is_string($tenantId) && $tenantId !== '') {
            return $this->syncSingleTenant($tenantId);
        }

        return $this->syncAllTenants();
    }

    private function syncSingleTenant(string $tenantId): int
    {
        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            $this->error("Tenant with ID '{$tenantId}' not found.");

            return Command::FAILURE;
        }

        $result = SubscriptionCycleSyncService::syncTenant($tenant);

        $this->info("Synced subscription for tenant: {$tenantId}");
        $this->table(
            ['Field', 'Value'],
            [
                ['Tenant ID', $result['tenant_id']],
                ['Previous Status', $result['previous_status']],
                ['Current Status', $result['current_status']],
                ['Payment Failed At', $result['payment_failed_at'] ?? 'N/A'],
                ['Changed', $result['changed'] ? 'Yes' : 'No'],
                ['Reason', $result['reason']],
            ]
        );

        return Command::SUCCESS;
    }

    private function syncAllTenants(): int
    {
        $this->info('Starting subscription cycle scan for all tenants...');

        $summary = SubscriptionCycleSyncService::syncAll();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tenants Scanned', (string) $summary['total_scanned']],
                ['Active', (string) $summary['active']],
                ['Past Due (Grace Period)', (string) $summary['past_due']],
                ['Suspended (Expired)', (string) $summary['suspended']],
                ['Status Changed', (string) $summary['changed']],
                ['Unchanged', (string) $summary['unchanged']],
            ]
        );

        $this->info('Subscription cycle scan completed successfully.');

        return Command::SUCCESS;
    }
}
