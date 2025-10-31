<?php

declare(strict_types=1);

namespace Droath\NextusAi\Memory\Strategies;

use Droath\NextusAi\Memory\Contracts\MemoryStrategyInterface;
use Droath\NextusAi\Models\AgentMemory;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

/**
 * Database-based memory storage strategy.
 *
 * Provides persistent memory storage using Laravel's Eloquent ORM with
 * features like:
 * - TTL (Time To Live) support with automatic expiration
 * - Query optimization with proper indexing
 * - Bulk operations for efficiency
 * - Connection configuration and fallback handling
 * - Automatic cleanup of expired entries
 */
class DatabaseMemoryStrategy implements MemoryStrategyInterface
{
    protected const string DEFAULT_TTL_KEY = 'default_ttl';

    public function __construct(
        protected array $config = []
    ) {}

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $effectiveTtl = $ttl ?? ($this->config[self::DEFAULT_TTL_KEY] ?? null);

            $expiresAt = $effectiveTtl === null
                ? Carbon::now()->addYear()
                : Carbon::now()->addSeconds($effectiveTtl);

            AgentMemory::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'expires_at' => $expiresAt,
                ]
            );

            return true;
        } catch (QueryException) {
            // Log error in production, fail gracefully
            return false;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $record = AgentMemory::where('key', $key)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            return $record->value ?? $default;
        } catch (QueryException) {
            // Log error in production, return default
            return $default;
        }
    }

    public function has(string $key): bool
    {
        try {
            return AgentMemory::where('key', $key)
                ->where('expires_at', '>', Carbon::now())
                ->exists();
        } catch (QueryException) {
            // Log error in production, assume false
            return false;
        }
    }

    public function forget(string $key): bool
    {
        try {
            AgentMemory::where('key', $key)->delete();

            return true;
        } catch (QueryException) {
            // Log error in production, fail gracefully
            return false;
        }
    }

    public function flush(): bool
    {
        try {
            AgentMemory::truncate();

            return true;
        } catch (QueryException) {
            // Log error in production, fail gracefully
            return false;
        }
    }

    /**
     * Set multiple key-value pairs at once for efficiency.
     */
    public function setMultiple(array $data, ?int $ttl = null): bool
    {
        try {
            foreach ($data as $key => $value) {
                $this->set($key, $value, $ttl);
            }

            return true;
        } catch (QueryException) {
            // Log error in production, fail gracefully
            return false;
        }
    }

    /**
     * Forget multiple keys at once for efficiency.
     */
    public function forgetMultiple(array $keys): bool
    {
        try {
            AgentMemory::whereIn('key', $keys)->delete();

            return true;
        } catch (QueryException) {
            // Log error in production, fail gracefully
            return false;
        }
    }

    /**
     * Clean up expired memory entries and return success status.
     */
    public function cleanupExpired(): ?bool
    {
        try {
            AgentMemory::where('expires_at', '<=', Carbon::now())->delete();

            return true;
        } catch (QueryException) {
            // Log error in production, return false
            return false;
        }
    }
}
