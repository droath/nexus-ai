<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\AgentTools;

use Droath\NextusAi\Tools\ToolProperty;
use Droath\NextusAi\Plugins\AgentTool\AgentToolPlugin;
use Droath\NextusAi\Attributes\AgentToolPluginMetadata;

#[AgentToolPluginMetadata(
    id: 'get_weather',
    label: 'Get Weather',
    description: 'Get the weather for a given location.',
)]
class GetWeatherTool extends AgentToolPlugin
{
    /**
     * {@inheritDoc}
     */
    public function execute(array $arguments): string
    {
        return "It's 59 degrees {$arguments['unit']} in {$arguments['location']} today!";
    }

    /**
     * {@inheritDoc}
     */
    public function properties(): array
    {
        return [
            ToolProperty::make('location', 'string')->required(),
            ToolProperty::make('unit', 'string')
                ->describe('The city and state, e.g. San Francisco, CA')
                ->withEnums(['celsius', 'fahrenheit']),
        ];
    }
}
