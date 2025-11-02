<?php

declare(strict_types=1);

namespace Droath\NextusAi\Agents\Memory;

use Throwable;

/**
 * Backward-compatible session memory implementation.
 *
 * This class maintains the existing SessionMemory interface while
 * providing a basic session-based implementation for compatibility.
 * For new implementations, use SessionMemoryStrategy instead.
 */
class SessionMemory extends BaseMemory
{
    protected string $prefix;

    public function __construct(string $prefix = 'agent_memory')
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            session([$this->getSessionKey($key) => $value]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return session($this->getSessionKey($key), $default);
        } catch (Throwable) {
            return $default;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        try {
            return session()->has($this->getSessionKey($key));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function forget(string $key): bool
    {
        try {
            session()->forget($this->getSessionKey($key));

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function flush(): bool
    {
        try {
            $keys = array_keys(session()->all());
            $prefix = $this->prefix.'.';

            foreach ($keys as $key) {
                if (str_starts_with($key, $prefix)) {
                    session()->forget($key);
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get the full session key with prefix.
     */
    protected function getSessionKey(string $key): string
    {
        return $this->prefix.'.'.$key;
    }
}
