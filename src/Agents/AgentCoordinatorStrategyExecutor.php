<?php

declare(strict_types=1);

namespace Droath\NextusAi\Agents;

use Illuminate\Support\Facades\Pipeline;
use Droath\NextusAi\Messages\UserMessage;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Agents\Contracts\AgentInterface;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Resources\Contracts\HasToolsInterface;
use Droath\NextusAi\Resources\Contracts\ResourceInterface;
use Droath\NextusAi\Resources\Contracts\HasMessagesInterface;
use Droath\NextusAi\Agents\ValueObject\AgentCoordinatorResponse;

/**
 * Define the agent strategy executor.
 */
final class AgentCoordinatorStrategyExecutor
{
    /**
     * @param Agent[] $agents
     *   The agents to execute.
     * @param AgentStrategy $strategy
     *   The agent executing strategy.
     * @param ResourceInterface $resource
     *   The agent LLM resource instance.
     */
    public function __construct(
        protected array $agents,
        protected AgentStrategy $strategy,
        protected ResourceInterface $resource,
    ) {}

    public function handle(): AgentCoordinatorResponse
    {
        return match ($this->strategy) {
            AgentStrategy::ROUTER => $this->handleRouter(),
            AgentStrategy::PARALLEL => $this->handleParallel(),
            AgentStrategy::SEQUENTIAL => $this->handleSequential(),
        };
    }

    protected function handleRouter(): AgentCoordinatorResponse
    {
        $resource = $this->resource;

        if ($resource instanceof HasToolsInterface) {
            $resource->withTools(
                $this->agentsAsTools()
            );
        }
        $coordinatorResponse = $resource();

        return AgentCoordinatorResponse::make(
            agents: $this->agents,
            strategy: $this->strategy,
            resource: $this->resource,
            coordinatorResponse: $coordinatorResponse,
        );
    }

    protected function handleParallel(): AgentCoordinatorResponse
    {
        $agentResponses = [];

        foreach ($this->agents as $index => $agent) {
            $agentResponses[$agent->name ?? $index] = $agent->run(
                clone $this->resource
            );
        }
        $messages = $this->toUserMessages($agentResponses);

        $coordinatorResponse = $this->invokeCoordinatorResource(
            $messages
        );

        return AgentCoordinatorResponse::make(
            agents: $this->agents,
            strategy: $this->strategy,
            resource: $this->resource,
            coordinatorResponse: $coordinatorResponse,
            agentResponses: $agentResponses
        );
    }

    protected function handleSequential(): AgentCoordinatorResponse
    {
        $agents = $this->agents;
        $agent = array_shift($this->agents);

        if ($agent instanceof AgentInterface) {
            $response = $agent->run(clone $this->resource);

            $agentResponse = Pipeline::send($response)
                ->through($this->agents)
                ->thenReturn();

            $coordinatorResponse = $this->invokeCoordinatorResource(
                $this->toUserMessages([$agentResponse])
            );

            return AgentCoordinatorResponse::make(
                agents: $agents,
                strategy: $this->strategy,
                resource: $this->resource,
                coordinatorResponse: $coordinatorResponse,
                agentResponses: [$agentResponse]
            );
        }

        return AgentCoordinatorResponse::make(
            agents: $this->agents,
            strategy: $this->strategy,
            resource: $this->resource,
            coordinatorResponse: null
        );
    }

    /**
     * Invoke the coordinator resource to get the response.
     */
    protected function invokeCoordinatorResource(
        array $messages
    ): ?NextusAiResponseMessage {
        if ($this->resource instanceof HasMessagesInterface) {
            return $this->resource->withMessages($messages)->__invoke();
        }

        return null;
    }

    /**
     * @return \Droath\NextusAi\Tools\Tool[]
     */
    protected function agentsAsTools(): array
    {
        $tools = [];

        foreach ($this->agents as $agent) {
            $tools[] = $agent->asTool();
        }

        return array_filter($tools);
    }

    protected function toUserMessages(array $responses): array
    {
        return collect($responses)
            ->map(function ($response) {
                $response = is_array($response)
                    ? json_encode($response, JSON_THROW_ON_ERROR)
                    : $response->__toString();

                return UserMessage::make($response);
            })->values()->all();
    }
}
