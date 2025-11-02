<?php

declare(strict_types=1);

namespace Droath\NextusAi\Console\Commands;

use Illuminate\Console\Command;
use Droath\NextusAi\Services\MemoryCleanupService;

/**
 * Console command for cleaning up expired memory entries.
 *
 * This command can be scheduled to run automatically or executed manually
 * to clean up expired memory entries across all configured strategies.
 */
class MemoryCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nextus-ai:memory:cleanup {--dry-run : Show what would be cleaned without actually cleaning}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up expired memory entries across all configured strategies';

    public function handle(MemoryCleanupService $cleanupService): int
    {
        $this->info('Starting memory cleanup...');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No actual cleanup will be performed');
            $stats = $cleanupService->getCleanupStats();
            $this->displayStats($stats);

            return self::SUCCESS;
        }

        $results = $cleanupService->cleanupAll();

        $this->displayResults($results);

        return self::SUCCESS;
    }

    /**
     * Display cleanup results in a formatted table.
     */
    protected function displayResults(array $results): void
    {
        $this->info("Cleanup completed at {$results['timestamp']}");
        $this->info("Successfully cleaned {$results['total_cleaned']} strategies");

        if (empty($results['strategies'])) {
            $this->warn('No strategies were configured for cleanup');

            return;
        }

        $tableData = [];
        foreach ($results['strategies'] as $strategy => $result) {
            $tableData[] = [
                'Strategy' => ucfirst($strategy),
                'Status' => $result['status'],
                'Error' => $result['error'] ?? 'N/A',
            ];
        }

        $this->table(['Strategy', 'Status', 'Error'], $tableData);
    }

    /**
     * Display cleanup statistics for dry-run mode.
     */
    protected function displayStats(array $stats): void
    {
        $this->info('Available memory strategies:');

        if (empty($stats)) {
            $this->warn('No strategies found');

            return;
        }

        $tableData = [];
        foreach ($stats as $strategy => $stat) {
            $tableData[] = [
                'Strategy' => ucfirst($strategy),
                'Available' => $stat['available'] ? 'Yes' : 'No',
                'Last Cleanup' => $stat['last_cleanup'] ?? 'Never',
                'Error' => $stat['error'] ?? 'N/A',
            ];
        }

        $this->table(['Strategy', 'Available', 'Last Cleanup', 'Error'], $tableData);
    }
}
