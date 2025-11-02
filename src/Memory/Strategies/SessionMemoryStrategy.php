<?php

declare(strict_types=1);

namespace Droath\NextusAi\Memory\Strategies;

use Throwable;
use Illuminate\Session\Store;
use Illuminate\Contracts\Session\Session;
use Droath\NextusAi\Memory\Contracts\MemoryStrategyInterface;
use Droath\NextusAi\Memory\Exceptions\MemoryStorageException;

/**
 * Session-based memory strategy for agent data storage.
 *
 * This strategy uses Laravel's session system to store agent memory
 * data temporarily for the duration of a user session. TTL parameters
 * are ignored as session data expires with the session itself.
 */
class SessionMemoryStrategy implements MemoryStrategyInterface
{
    private const string PREFIX_KEY = 'prefix';

    private const string DEFAULT_PREFIX = 'agent_memory';

    protected string $prefix;

    protected Session $session;

    public function __construct(
        protected array $config
    ) {
        $this->session = app(Session::class);
        $this->prefix = $this->config[self::PREFIX_KEY] ?? self::DEFAULT_PREFIX;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $sessionKey = $this->buildSessionKey($key);
            $this->session->put($sessionKey, $value);

            return true;
        } catch (Throwable $exception) {
            throw new MemoryStorageException(
                "Failed to store memory key '{$key}' in session: {$exception->getMessage()}",
                0,
                $exception
            );
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $sessionKey = $this->buildSessionKey($key);

            return $this->session->get($sessionKey, $default);
        } catch (Throwable $exception) {
            throw new MemoryStorageException(
                "Failed to retrieve memory key '{$key}' from session: {$exception->getMessage()}",
                0,
                $exception
            );
        }
    }

    public function has(string $key): bool
    {
        try {
            $sessionKey = $this->buildSessionKey($key);

            return $this->session->has($sessionKey);
        } catch (Throwable $exception) {
            throw new MemoryStorageException(
                "Failed to check memory key '{$key}' in session: {$exception->getMessage()}",
                0,
                $exception
            );
        }
    }

    public function forget(string $key): bool
    {
        try {
            $sessionKey = $this->buildSessionKey($key);
            $this->session->forget($sessionKey);

            return true;
        } catch (Throwable $exception) {
            throw new MemoryStorageException(
                "Failed to forget memory key '{$key}' from session: {$exception->getMessage()}",
                0,
                $exception
            );
        }
    }

    public function flush(): bool
    {
        try {
            foreach ($this->session->all() as $sessionKey => $value) {
                if (str_starts_with($sessionKey, $this->prefix)) {
                    $this->session->forget($sessionKey);
                }
            }

            return true;
        } catch (Throwable $exception) {
            throw new MemoryStorageException(
                "Failed to flush memory from session: {$exception->getMessage()}",
                0,
                $exception
            );
        }
    }

    public function cleanupExpired(): ?bool
    {
        // Session strategy doesn't need manual cleanup as Laravel handles session expiration
        // Return true to indicate cleanup was successful (no-op for sessions)
        return true;
    }

    public function setSession(Store $session): static
    {
        $this->session = $session;

        return $this;
    }

    private function buildSessionKey(string $key): string
    {
        return "$this->prefix.$key";
    }
}
