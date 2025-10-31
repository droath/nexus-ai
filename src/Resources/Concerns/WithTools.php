<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Concerns;

use Droath\NextusAi\Resources\Contracts\HasToolTransformerInterface;
use Droath\NextusAi\Tools\Tool;
use Illuminate\Support\Collection;

trait WithTools
{
    protected array|Collection $tools = [];

    /**
     * {@inheritDoc}
     */
    public function withTools(array $tools): static
    {
        $this->tools = Collection::make($tools);

        return $this;
    }

    /**
     * Resolve the resource tools.
     */
    protected function resolveTools(): array
    {
        if ($this->tools instanceof Collection) {
            return $this->tools->map(function ($tool) {
                if ($tool instanceof Tool) {
                    if ($this instanceof HasToolTransformerInterface) {
                        return self::transformTool($tool);
                    }

                    return $this->driver::transformTool($tool);
                }

                return $tool;
            })->toArray();
        }

        return $this->tools;
    }
}
