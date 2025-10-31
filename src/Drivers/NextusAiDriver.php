<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers;

use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use Droath\NextusAi\Tools\Tool;

abstract class NextusAiDriver implements DriverInterface
{
    /**
     * {@inheritDoc}
     */
    public static function transformTool(Tool $tool): array
    {
        return $tool->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public static function transformUserMessage(string $content): string|array
    {
        return $content;
    }
}
