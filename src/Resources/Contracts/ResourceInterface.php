<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

interface ResourceInterface extends HasModelInterface
{
    /**
     * Invoke the resource response.
     */
    public function __invoke(): mixed;

    /**
     * Execute the resource with explicit method call.
     *
     * This method provides a more explicit alternative to __invoke()
     * with better IDE support and clearer intent.
     */
    public function call(): mixed;
}
