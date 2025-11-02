<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers\Contracts;

use Droath\NextusAi\Resources\Contracts\StructuredResourceInterface;

interface HasStructuredInterface
{
    /**
     * Define the LLM structured resource.
     */
    public function structured(): StructuredResourceInterface;
}
