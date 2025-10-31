<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\Agents;

use Droath\NextusAi\Plugins\Agents\AgentPlugin;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Attributes\AgentPluginMetadata;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

#[AgentPluginMetadata(
    id: 'weather_agent',
    label: 'Weather Agent',
    provider: LlmProvider::OPENAI,
    tools: ['get_weather', 'get_wind_speed'],
    description: 'A weather agent that fetches the latest weather information.',
)]
class WeatherAgent extends AgentPlugin
{
    /**
     * {@inheritDoc}
     */
    protected function systemInstruction(): ?string
    {
        return 'You are a helpful weather agent, which will help users with weather information.';
    }

    /**
     * {@inheritDoc}
     */
    protected function transformResponse(NextusAiResponseMessage|array $response): array
    {
        return [
            'weather' => $response->message,
        ];
    }
}
