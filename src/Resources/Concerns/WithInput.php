<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Concerns;

trait WithInput
{
    protected string|array $input = '';

    /**
     * @return $this
     */
    public function withInput(string|array $input): static
    {
        $this->input = $input;

        return $this;
    }
}
