<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\Agents;

use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Plugins\Agents\AgentPlugin;
use Droath\NextusAi\Attributes\AgentPluginMetadata;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

#[AgentPluginMetadata(
    id: 'sport_agent',
    label: 'Sport Agent',
    provider: LlmProvider::OPENAI,
    tools: ['get_sports_news'],
    description: 'A sports agent that fetches the latest sports news.',
)]
class SportAgent extends AgentPlugin
{
    /**
     * {@inheritDoc}
     */
    protected function systemInstruction(): ?string
    {
        return 'You are a helpful sports agent, you task to help users fetch the latest sports news.';
    }

    /**
     * {@inheritDoc}
     */
    protected function transformResponse(NextusAiResponseMessage|array $response): array
    {
        return [
            'sports' => $response->message,
        ];
    }
}
