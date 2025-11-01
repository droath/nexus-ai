<?php

declare(strict_types=1);

namespace Droath\NextusAi\Memory\Exceptions;

use Exception;

/**
 * Base exception for memory-related errors.
 *
 * All memory system exceptions should extend this class for consistent
 * error handling and categorization.
 */
class MemoryException extends Exception
{
    protected string $memoryKey = '';

    protected string $strategy = '';

    /**
     * Set the memory key associated with this exception.
     */
    public function setMemoryKey(string $key): static
    {
        $this->memoryKey = $key;

        return $this;
    }

    /**
     * Get the memory key associated with this exception.
     */
    public function getMemoryKey(): string
    {
        return $this->memoryKey;
    }

    /**
     * Set the strategy associated with this exception.
     */
    public function setStrategy(string $strategy): static
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Get the strategy associated with this exception.
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * Create a contextualized exception instance.
     */
    public static function forKey(string $key, string $message = '', ?\Throwable $previous = null): static
    {
        /** @phpstan-ignore-next-line Safe usage of new static() - all subclasses use default Exception constructor */
        $exception = new static($message ?: "Memory error for key: {$key}", 0, $previous);

        return $exception->setMemoryKey($key);
    }

    /**
     * Create a strategy-specific exception instance.
     */
    public static function forStrategy(string $strategy, string $message = '', ?\Throwable $previous = null): static
    {
        /** @phpstan-ignore-next-line Safe usage of new static() - all subclasses use default Exception constructor */
        $exception = new static($message ?: "Memory error in strategy: {$strategy}", 0, $previous);

        return $exception->setStrategy($strategy);
    }
}
