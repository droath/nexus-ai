<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tools;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * Define the tool creation class.
 */
class Tool implements Arrayable
{
    protected bool $strict = false;

    protected ?\Closure $function = null;

    protected ?string $description = null;

    /**
     * @var \Droath\NextusAi\Tools\ToolProperty[]
     */
    protected array $properties = [];

    protected function __construct(
        public readonly string $name,
    ) {}

    public static function make(string $name): self
    {
        return new self(Str::snake($name));
    }

    /**
     * @param mixed ...$args
     */
    public function __invoke(...$args): string
    {
        $function = $this->function;

        if (! isset($function)) {
            throw new \RuntimeException('Tool function is not defined.');
        }

        return $function($args);
    }

    /**
     * @return $this
     */
    public function using(\Closure $function): self
    {
        $this->function = $function;

        return $this;
    }

    /**
     * @return $this
     */
    public function strict(): self
    {
        $this->strict = true;

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
     * @param \Droath\NextusAi\Tools\ToolProperty[] $properties
     *
     * @return $this
     */
    public function withProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function hasProperties(): bool
    {
        return ! empty($this->properties);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $properties = collect($this->properties);

        return [
            'name' => $this->name,
            'strict' => $this->strict,
            'description' => $this->description,
            'properties' => $properties,
            'required' => $properties
                ->filter(fn (ToolProperty $property) => $property->required)
                ->map(fn (ToolProperty $property) => $property->name)
                ->values()
                ->toArray(),
        ];
    }
}
