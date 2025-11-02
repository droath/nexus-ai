<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\Agents;

use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Plugins\Agents\AgentPlugin;
use Droath\NextusAi\Attributes\AgentPluginMetadata;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

#[AgentPluginMetadata(
    id: 'research_agent',
    label: 'Research Agent',
    provider: LlmProvider::OPENAI,
    tools: ['search_academic_articles'],
    description: 'A research agent fetches datasets from the latest academic articles.',
)]
class ResearchAgent extends AgentPlugin
{
    /**
     * {@inheritDoc}
     */
    protected function systemInstruction(): ?string
    {
        return 'You are a helpful research agent, you task to help users fetch the latest academic articles.';
    }

    /**
     * {@inheritDoc}
     */
    protected function transformResponse(NextusAiResponseMessage|array $response): array
    {
        return [
            'research' => $response->message,
        ];
    }
}
