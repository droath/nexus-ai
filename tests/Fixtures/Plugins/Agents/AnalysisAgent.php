<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\Agents;

use Droath\NextusAi\Plugins\Agents\AgentPlugin;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Attributes\AgentPluginMetadata;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

#[AgentPluginMetadata(
    id: 'analysis_agent',
    label: 'Analysis Agent',
    provider: LlmProvider::OPENAI,
    description: 'You analyze the data provided by the research team.',
)]
class AnalysisAgent extends AgentPlugin
{
    /**
     * {@inheritDoc}
     */
    protected function systemInstruction(): ?string
    {
        return 'You are a helpful agent that analyzes the data provided by the research team.';
    }

    /**
     * {@inheritDoc}
     */
    protected function transformResponse(NextusAiResponseMessage|array $response): array
    {
        return [
            'analyze' => $response->message,
        ];
    }
}
