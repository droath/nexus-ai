<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers\Contracts;

use Droath\NextusAi\Resources\Contracts\ResponsesResourceInterface;

interface HasResponsesInterface
{
    /**
     * Define the LLM responses resource.
     */
    public function responses(): ResponsesResourceInterface;
}
