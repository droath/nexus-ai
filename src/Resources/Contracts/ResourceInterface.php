<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

interface ResourceInterface extends HasModelInterface
{
    /**
     * Invoke the resource response.
     */
    public function __invoke(): mixed;
}
