<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

interface HasResponseFormatInterface
{
    /***
     * @return $this
     */
    public function withResponseFormat(array $responseFormat): static;
}
