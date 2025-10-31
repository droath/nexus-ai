<?php

declare(strict_types=1);

namespace Droath\NextusAi\Schemas;

use Illuminate\Contracts\Support\Arrayable;

class ArraySchema extends BaseSchema implements Arrayable
{
    protected array|ObjectSchema $items = [];

    /**
     * @return $this
     */
    public function setItems(ObjectSchema|array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'type' => 'array',
            'items' => is_array($this->items)
                ? collect($this->items)->map(function ($item) {
                    return $item instanceof Arrayable ? $item->toArray() : $item;
                })->toArray()
                : $this->items->toArray(),
            'description' => $this->description,
        ];
    }
}
