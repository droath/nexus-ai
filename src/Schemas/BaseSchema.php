<?php

declare(strict_types=1);

namespace Droath\NextusAi\Schemas;

abstract class BaseSchema
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $required = false,
    ) {}
}
