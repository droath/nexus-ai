<?php

declare(strict_types=1);

namespace Droath\NextusAi\Memory;

use InvalidArgumentException;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Value object representing a memory strategy definition.
 *
 * Encapsulates the type (strategy name) and configuration options
 * for creating memory strategy instances.
 */
final readonly class MemoryDefinition implements Arrayable
{
    /**
     * Valid memory strategy types.
     */
    public const array VALID_TYPES = ['session', 'database', 'null'];

    public function __construct(
        protected string $type,
        protected array $configs = []
    ) {
        $this->validateType($type);
    }

    /**
     * Create a MemoryDefinition from an array.
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['type'])) {
            throw new InvalidArgumentException(
                'Memory definition must include a type'
            );
        }

        return new self(
            type: $data['type'],
            configs: $data['configs'] ?? []
        );
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'configs' => $this->configs,
        ];
    }

    /**
     * Get the memory definition type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get all memory definition configurations.
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

    /**
     * Get a specific configuration value.
     */
    public function getConfig(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->configs[$key] ?? $default;
    }

    /**
     * Check if a configuration exists.
     */
    public function hasConfig(string $key): bool
    {
        return array_key_exists($key, $this->configs);
    }

    /**
     * Validate that the strategy type is supported.
     */
    private function validateType(string $type): void
    {
        if (! in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                "Invalid memory strategy type '{$type}'. Valid types: ".
                implode(', ', self::VALID_TYPES)
            );
        }
    }
}
