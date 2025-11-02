<?php

declare(strict_types=1);

namespace Droath\NextusAi\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Droath\NextusAi\Memory\MemoryDefinition;
use Droath\NextusAi\Memory\MemoryStrategyFactory;
use Droath\NextusAi\Memory\Contracts\MemoryStrategyInterface;

/**
 * Service for cleaning up expired memory entries across all configured strategies.
 *
 * This service iterates through all configured memory strategies and triggers
 * their cleanup processes. It's designed to be called by Laravel's scheduler
 * to maintain memory storage performance.
 */
class MemoryCleanupService
{
    /**
     * Available memory strategy types.
     */
    protected const array STRATEGY_TYPES = ['session', 'database', 'null'];

    /**
     * Run memory cleanup across all configured strategies.
     *
     * @return array Summary of cleanup results
     */
    public function cleanupAll(): array
    {
        Log::info('Starting memory cleanup across all strategies');

        $results = [];
        $successfulStrategies = 0;

        foreach ($this->getConfiguredStrategies() as $strategyType) {
            try {
                $success = $this->cleanupStrategy($strategyType);
                $results[$strategyType] = [
                    'status' => $success ? 'success' : 'failed',
                ];

                if ($success) {
                    $successfulStrategies++;
                    Log::info("Memory cleanup: {$strategyType} strategy completed successfully");
                }

            } catch (Exception $e) {
                $results[$strategyType] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];

                Log::error("Memory cleanup failed for {$strategyType} strategy: {$e->getMessage()}");
            }
        }

        Log::info("Memory cleanup completed. {$successfulStrategies} strategies cleaned successfully");

        return [
            'total_cleaned' => $successfulStrategies,
            'strategies' => $results,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Cleanup expired entries for a specific strategy type.
     *
     * @param string $strategyType
     *   The memory strategy type to clean up.
     *
     * @return bool|null
     *   Number of entries cleaned
     */
    public function cleanupStrategy(string $strategyType): ?bool
    {
        $strategy = $this->createStrategy($strategyType);

        if (! $strategy) {
            Log::warning(
                "Skipping cleanup for unsupported strategy: {$strategyType}"
            );

            return false;
        }

        return $strategy->cleanupExpired();
    }

    /**
     * Get cleanup statistics for monitoring purposes.
     */
    public function getCleanupStats(): array
    {
        $stats = [];

        foreach ($this->getConfiguredStrategies() as $strategyType) {
            try {
                $strategy = $this->createStrategy($strategyType);

                if ($strategy) {
                    // For strategies that support it, we could add methods to get stats
                    // For now, just indicate the strategy is available
                    $stats[$strategyType] = [
                        'available' => true,
                        'last_cleanup' => cache()->get("memory_cleanup_last_{$strategyType}", null),
                    ];
                } else {
                    $stats[$strategyType] = ['available' => false];
                }

            } catch (Exception $e) {
                $stats[$strategyType] = [
                    'available' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    /**
     * Get a list of configured memory strategies from config.
     *
     * @return Collection<string>
     */
    protected function getConfiguredStrategies(): Collection
    {
        $enabledStrategies = config(
            'nextus-ai.memory.cleanup.strategies',
            self::STRATEGY_TYPES
        );

        // Filter to only include valid strategy types
        return collect($enabledStrategies)
            ->intersect(self::STRATEGY_TYPES)
            ->values();
    }

    /**
     * Create a memory strategy instance for cleanup.
     */
    protected function createStrategy(
        string $strategyType
    ): ?MemoryStrategyInterface {
        try {
            // Get the default configuration for the strategy
            $configs = config("nextus-ai.memory.strategies.{$strategyType}", []);

            $definition = new MemoryDefinition($strategyType, $configs);
            $factory = new MemoryStrategyFactory($definition);

            return $factory->createInstance();

        } catch (Exception $e) {
            Log::error("Failed to create {$strategyType} strategy for cleanup: {$e->getMessage()}");

            return null;
        }
    }
}
