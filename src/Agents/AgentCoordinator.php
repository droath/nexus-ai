<?php

declare(strict_types=1);

namespace Droath\NextusAi\Agents;

use Illuminate\Support\Arr;
use Droath\NextusAi\Messages\UserMessage;
use Droath\NextusAi\Messages\SystemMessage;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Agents\Contracts\AgentInterface;
use Droath\NextusAi\Agents\Contracts\AgentMemoryInterface;
use Droath\NextusAi\Resources\Contracts\ResourceInterface;
use Droath\NextusAi\Resources\Contracts\HasMessagesInterface;
use Droath\NextusAi\Agents\Contracts\AgentCoordinatorInterface;
use Droath\NextusAi\Agents\ValueObject\AgentCoordinatorResponse;
use Droath\NextusAi\Resources\Contracts\HasResponseFormatInterface;

class AgentCoordinator implements AgentCoordinatorInterface
{
    protected array $input;

    protected array $responseFormat = [];

    protected ?SystemMessage $systemPrompt = null;

    protected ?AgentMemoryInterface $memory = null;

    /**
     * Define the agent coordinator constructor.
     *
     * @param string|array $input
     *   The input user messages.
     * @param array $agents
     *   The agents on which to coordinate.
     * @param \Droath\NextusAi\Agents\Enums\AgentStrategy $strategy
     *   The agent coordinator strategy.
     */
    protected function __construct(
        string|array $input,
        protected array $agents,
        protected AgentStrategy $strategy,
    ) {
        $this->input = ! is_array($input)
            ? [UserMessage::make($input)]
            : $input;
    }

    /**
     * {@inheritDoc}
     */
    public static function make(
        string|array $input,
        array $agents,
        AgentStrategy $strategy
    ): self {
        return new self($input, $agents, $strategy);
    }

    /**
     * {@inheritDoc}
     */
    public function setSystemPrompt(string $prompt): static
    {
        $this->systemPrompt = SystemMessage::make($prompt);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setResponseFormat(array $format): static
    {
        $this->responseFormat = $format;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addAgent(AgentInterface $agent): static
    {
        $this->agents[] = $agent;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addAgents(array $agents): static
    {
        foreach ($agents as $agent) {
            $this->addAgent($agent);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMemory(AgentMemoryInterface $memory): static
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function run(ResourceInterface $resource): AgentCoordinatorResponse
    {
        $this->prepare($resource);

        return (new AgentCoordinatorStrategyExecutor(
            $this->agents,
            $this->strategy,
            $resource
        ))->handle();
    }

    /**
     * Prepare the agents based on strategy.
     */
    protected function prepare(ResourceInterface $resource): void
    {
        $this->prepareAgents($resource);

        if (
            $resource instanceof HasResponseFormatInterface
            && ($responseFormat = $this->responseFormat)
        ) {
            $resource->withResponseFormat($responseFormat);
        }

        if (
            ($this->strategy === AgentStrategy::ROUTER)
            && $resource instanceof HasMessagesInterface
        ) {
            $resource->withMessages(array_filter([
                $this->systemPrompt,
                ...$this->input,
            ]));
        }

        if ($this->strategy === AgentStrategy::PARALLEL) {
            Arr::map($this->agents, function (AgentInterface $agent) {
                $agent->addInputs($this->input);
            });
        }

        if ($this->strategy === AgentStrategy::SEQUENTIAL) {
            Arr::first($this->agents)->addInputs($this->input);
        }
    }

    /**
     * Prepare the agents for execution.
     */
    protected function prepareAgents(ResourceInterface $resource): void
    {
        foreach ($this->agents as $agent) {
            if (! $agent instanceof AgentInterface) {
                continue;
            }
            $agent->setResource(clone $resource);

            if ($memory = $this->memory) {
                $agent->setMemory($memory);
            }
        }
    }
}
