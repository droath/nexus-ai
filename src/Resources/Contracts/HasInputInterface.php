<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

interface HasInputInterface
{
    /**
     * @return $this
     */
    public function withInput(string|array $input): static;
}
