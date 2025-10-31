<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers\Contracts;

use Droath\NextusAi\Tools\Tool;

interface DriverInterface
{
    /**
     * Transform driver tools to their specified format.
     */
    public static function transformTool(Tool $tool): array;

    /**
     * Transform the driver user message to their specified format.
     */
    public static function transformUserMessage(string $content): string|array;
}
