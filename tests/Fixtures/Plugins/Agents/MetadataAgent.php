<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\Agents;

use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Plugins\Agents\AgentPlugin;
use Droath\NextusAi\Attributes\AgentPluginMetadata;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

#[AgentPluginMetadata(
    id: 'metadata_agent',
    label: 'Metadata Agent',
    provider: LlmProvider::OPENAI,
    description: 'A metadata agent for generating metadata based on provided content.',
)]
class MetadataAgent extends AgentPlugin
{
    /**
     * {@inheritDoc}
     */
    protected function systemInstruction(): ?string
    {
        return 'You are a metadata agent for generating metadata';
    }

    /**
     * {@inheritDoc}
     */
    protected function transformResponse(
        NextusAiResponseMessage|array $response
    ): NextusAiResponseMessage {
        return $response;
    }
}
