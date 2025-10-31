<?php

declare(strict_types=1);

namespace Droath\NextusAi\Schemas;

use Illuminate\Contracts\Support\Arrayable;

class JsonSchema implements Arrayable
{
    protected ObjectSchema $schema;

    public function __construct(
        protected string $name
    ) {}

    /**
     * @return $this
     */
    public function setSchema(ObjectSchema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'type' => 'json_schema',
            'name' => $this->name,
            'schema' => $this->schema->toArray(),
        ];
    }
}
