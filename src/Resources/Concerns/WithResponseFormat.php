<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Concerns;

trait WithResponseFormat
{
    protected array $responseFormat = [];

    /**
     * {@inheritDoc}
     */
    public function withResponseFormat(array $responseFormat): static
    {
        $this->responseFormat = $responseFormat;

        return $this;
    }
}
