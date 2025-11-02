<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

use Droath\NextusAi\Tools\Tool;

interface HasToolTransformerInterface
{
    /**
     * Define the tool transformation structure based on the LLM provider.
     *
     * Tool transformations can also be defined within the LLM driver class.
     * However, since tool definitions may change per LLM resource, it makes
     * sense to define them here as well.
     *
     * @param Tool $tool
     *   The tool generic tool definition.
     */
    public static function transformTool(Tool $tool): array;
}
