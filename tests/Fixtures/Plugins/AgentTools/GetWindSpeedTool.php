<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\AgentTools;

use Droath\NextusAi\Tools\ToolProperty;
use Droath\NextusAi\Plugins\AgentTool\AgentToolPlugin;
use Droath\NextusAi\Attributes\AgentToolPluginMetadata;

#[AgentToolPluginMetadata(
    id: 'get_wind_speed',
    label: 'Get Wind Speed',
    description: 'Get the wind speed for a given location.',
)]
class GetWindSpeedTool extends AgentToolPlugin
{
    /**
     * {@inheritDoc}
     */
    public function execute(array $arguments): string
    {
        return "The wind speed is 10 miles per hour in {$arguments['location']} today!";
    }

    /**
     * {@inheritDoc}
     */
    public function properties(): array
    {
        return [
            ToolProperty::make('location', 'string')->required(),
        ];
    }
}
