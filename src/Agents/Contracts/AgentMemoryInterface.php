<?php

declare(strict_types=1);

namespace Droath\NextusAi\Agents\Contracts;

/**
 * Interface for agent memory management.
 *
 * Provides key-value storage capabilities for agents with support for TTL,
 * different storage strategies, and lifecycle management.
 */
interface AgentMemoryInterface
{
    /**
     * Store a value in memory.
     *
     * @param string $key The memory key
     * @param mixed $value The value to store (will be JSON serialized)
     * @param int|null $ttl Optional time-to-live in seconds
     *
     * @return bool True on success, false on failure
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Retrieve a value from memory.
     *
     * @param string $key The memory key
     * @param mixed $default The default value to return if key doesn't exist
     *
     * @return mixed The stored value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a key exists in memory.
     *
     * @param string $key The memory key
     *
     * @return bool True if key exists and is not expired
     */
    public function has(string $key): bool;

    /**
     * Remove a value from memory.
     *
     * @param string $key The memory key
     *
     * @return bool True on success, false on failure
     */
    public function forget(string $key): bool;

    /**
     * Clear all memory.
     *
     * @return bool True on success, false on failure
     */
    public function flush(): bool;
}
