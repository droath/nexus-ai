<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

interface HasModelInterface
{
    /**
     * Set the resource model.
     *
     * @param string $model
     *   The resource model to use.
     */
    public function withModel(string $model): static;
}
