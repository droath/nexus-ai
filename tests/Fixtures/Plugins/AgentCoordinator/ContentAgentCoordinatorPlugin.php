<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\AgentCoordinator;

use Droath\NextusAi\Agents\Agent;
use Droath\NextusAi\Facades\NextusAi;
use Droath\NextusAi\Schemas\JsonSchema;
use Droath\NextusAi\Schemas\ArraySchema;
use Droath\NextusAi\Schemas\ObjectSchema;
use Droath\NextusAi\Schemas\StringSchema;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Resources\Contracts\ResourceInterface;
use Droath\NextusAi\Attributes\AgentCoordinatorPluginMetadata;
use Droath\NextusAi\Plugins\AgentCoordinator\AgentCoordinatorPlugin;

#[AgentCoordinatorPluginMetadata(
    id: 'content_agent_coordinator',
    label: 'Content Agent Coordinator',
    provider: LlmProvider::OPENAI,
    strategy: AgentStrategy::PARALLEL,
    agents: ['metadata_agent']
)]
class ContentAgentCoordinatorPlugin extends AgentCoordinatorPlugin
{
    /**
     * {@inheritDoc}
     */
    public function agents(): array
    {
        return [
            Agent::make(name: 'content_agent')
                ->setSystemPrompt('You are a content agent for generating content')
                ->addInput('Generate content based on the provided content from the coordinator.')
                ->setResponseFormat(
                    (new JsonSchema('content_response'))
                        ->setSchema($this->responseFormatSchema())
                        ->toArray()
                )->transformResponseUsing(function (NextusAiResponseMessage $response) {
                    return $response->toArray();
                }),
        ];
    }

    protected function resource(): ResourceInterface
    {
        return NextusAi::structured($this->provider());
    }

    protected function responseFormatSchema(): ObjectSchema
    {
        return (new ObjectSchema())
            ->setProperties([
                new StringSchema(
                    name: 'title',
                    description: 'A generated title is based on the content.',
                    required: true
                ),
                new StringSchema(
                    name: 'content',
                    description: 'The content with only h3 headings. The content is required to be formated as Markdown.',
                    required: true,
                ),
                (new ArraySchema(
                    name: 'tags',
                    description: 'The tags are generated based on the content. There is a maximum of three tags.',
                    required: true,
                ))->setItems(['type' => 'string']),
            ]);
    }
}
