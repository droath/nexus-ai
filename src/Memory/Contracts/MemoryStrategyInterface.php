<?php

declare(strict_types=1);

namespace Droath\NextusAi\Memory\Contracts;

use Droath\NextusAi\Agents\Contracts\AgentMemoryInterface;

/**
 * Interface for memory strategy implementations.
 *
 * Extends AgentMemoryInterface to provide strategy-specific functionality
 * for different memory storage backends (session, database, etc.).
 */
interface MemoryStrategyInterface extends AgentMemoryInterface
{
    /**
     * Cleanup expired entries (strategy-specific implementation).
     *
     * @return bool|null
     *   Number of entries cleaned up
     */
    public function cleanupExpired(): ?bool;
}
