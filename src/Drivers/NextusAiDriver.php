<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers;

use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Drivers\Contracts\DriverInterface;

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
