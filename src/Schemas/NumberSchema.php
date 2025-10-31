<?php

declare(strict_types=1);

namespace Droath\NextusAi\Schemas;

use Illuminate\Contracts\Support\Arrayable;

class NumberSchema extends BaseSchema implements Arrayable
{
    public function toArray(): array
    {
        return [
            'type' => 'number',
            'description' => $this->description,
        ];
    }
}
