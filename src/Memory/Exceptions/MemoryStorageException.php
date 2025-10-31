<?php

declare(strict_types=1);

namespace Droath\NextusAi\Memory\Exceptions;

/**
 * Exception thrown when memory storage operations fail.
 *
 * Used for errors during set, get, forget, or flush operations
 * when the underlying storage mechanism encounters issues.
 */
class MemoryStorageException extends MemoryException
{
    /**
     * Create an exception for a failed set operation.
     */
    public static function setFailed(string $key, string $strategy = '', ?\Throwable $previous = null): static
    {
        $message = "Failed to set memory key: {$key}";
        if ($strategy) {
            $message .= " using {$strategy} strategy";
        }

        return static::forKey($key, $message, $previous)->setStrategy($strategy);
    }

    /**
     * Create an exception for a failed get operation.
     */
    public static function getFailed(string $key, string $strategy = '', ?\Throwable $previous = null): static
    {
        $message = "Failed to get memory key: {$key}";
        if ($strategy) {
            $message .= " using {$strategy} strategy";
        }

        return static::forKey($key, $message, $previous)->setStrategy($strategy);
    }

    /**
     * Create an exception for a failed forget operation.
     */
    public static function forgetFailed(string $key, string $strategy = '', ?\Throwable $previous = null): static
    {
        $message = "Failed to forget memory key: {$key}";
        if ($strategy) {
            $message .= " using {$strategy} strategy";
        }

        return static::forKey($key, $message, $previous)->setStrategy($strategy);
    }

    /**
     * Create an exception for a failed flush operation.
     */
    public static function flushFailed(string $strategy = '', ?\Throwable $previous = null): static
    {
        $message = 'Failed to flush memory';
        if ($strategy) {
            $message .= " using {$strategy} strategy";
        }

        return static::forStrategy($strategy, $message, $previous);
    }
}
