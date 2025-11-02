<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tools;

use RuntimeException;
use Illuminate\Support\Str;

/**
 * Define the tool property creation class.
 */
final class ToolProperty
{
    public bool $required = false;

    protected array $enum = [];

    protected ?string $description = null;

    private function __construct(
        public readonly string $name,
        protected readonly string $type,
    ) {}

    public static function make(
        string $name,
        string $type,
    ): self {
        return new self(Str::snake($name), $type);
    }

    /**
     * @return $this
     */
    public function withEnums(array $enum): self
    {
        if ($this->type !== 'string') {
            throw new RuntimeException(
                'Enum can only be used with string types.'
            );
        }

        $this->enum = $enum;

        return $this;
    }

    /**
     * @return $this
     */
    public function describe(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return $this
     */
    public function required(): self
    {
        $this->required = true;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'enum' => $this->enum,
            'description' => $this->description,
        ];
    }
}
