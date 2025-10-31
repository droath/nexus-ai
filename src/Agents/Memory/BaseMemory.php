<?php

declare(strict_types=1);

namespace Droath\NextusAi\Agents\Memory;

use Droath\NextusAi\Agents\Contracts\AgentMemoryInterface;

/**
 * Base memory class providing common functionality.
 *
 * This is maintained as an abstract class to preserve existing architecture
 * while providing backward compatibility.
 */
abstract class BaseMemory implements AgentMemoryInterface
{
    /**
     * {@inheritDoc}
     */
    abstract public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * {@inheritDoc}
     */
    abstract public function has(string $key): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function forget(string $key): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function flush(): bool;
}
