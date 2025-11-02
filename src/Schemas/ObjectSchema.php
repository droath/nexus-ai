<?php

declare(strict_types=1);

namespace Droath\NextusAi\Schemas;

use Illuminate\Contracts\Support\Arrayable;

class ObjectSchema implements Arrayable
{
    protected array $properties = [];

    public function __construct(
        public ?string $name = null,
        public bool $required = false,
        protected bool $additionalProperties = false
    ) {}

    /**
     * @return $this
     */
    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return $this
     */
    public function addProperty(
        self|BaseSchema $property
    ): self {
        $this->properties[] = $property;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'type' => 'object',
            'required' => $this->required(),
            'properties' => collect($this->properties)->mapWithKeys(
                fn ($property) => [$property->name => $property->toArray()]
            )->toArray(),
            'additionalProperties' => $this->additionalProperties,
        ];
    }

    protected function required(): array
    {
        return collect($this->properties)
            ->filter(function ($property) {
                return $property->required ?? false;
            })
            ->pluck('name')
            ->toArray();
    }
}
