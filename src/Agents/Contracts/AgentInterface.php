<?php

declare(strict_types=1);

namespace Droath\NextusAi\Agents\Contracts;

use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Messages\MessageBase;
use Droath\NextusAi\Messages\SystemMessage;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Resources\Contracts\ResourceInterface;

/**
 * Define the agent interface.
 */
interface AgentInterface
{
    /**
     * Create the agent instance.
     */
    public static function make(
        array $inputs,
        array $tools = [],
        ?string $name = null
    ): self;

    /**
     * Get the agent name.
     */
    public function name(): ?string;

    /**
     * Set the agent name.
     *
     * @return $this
     */
    public function setName(string $name): static;

    /**
     * Get the agent description on what the agent does.
     */
    public function description(): ?string;

    /**
     * Set the agent description.
     *
     * @return $this
     */
    public function setDescription(string $description): static;

    /**
     * Invoke the agent response used in sequential strategy executor.
     */
    public function __invoke(
        NextusAiResponseMessage $response,
        \Closure $next
    ): NextusAiResponseMessage|null|array;

    /**
     * Set the agent modal.
     */
    public function setModal(string $modal): static;

    /**
     * Add an input to the agent instance
     *
     * @return $this
     */
    public function addInput(string|MessageBase $input): static;

    /**
     * Add inputs to the agent instance.
     *
     * @return $this
     */
    public function addInputs(array $input): static;

    /**
     * Get the inputs for the agent instance.
     */
    public function getInputs(): array;

    /**
     * Add a tool to the agent instance.
     *
     * @return $this
     */
    public function addTool(Tool $tool): static;

    /**
     * Add tools to the agent instance.
     *
     * @return $this
     */
    public function addTools(array $tools): static;

    /**
     * Convert the agent instance to a tool.
     */
    public function asTool(): ?Tool;

    /**
     * Set the system prompt to the agent instance.
     *
     * @return $this
     */
    public function setSystemPrompt(SystemMessage|string $prompt): static;

    /**
     * Set the response format to the agent instance.
     *
     * @return $this
     */
    public function setResponseFormat(array $format): static;

    /**
     * Set the agent memory instance.
     *
     * @return $this
     */
    public function setMemory(AgentMemoryInterface $memory): static;

    /**
     * Set the agent resource.
     *
     * @return $this
     */
    public function setResource(ResourceInterface $resource): static;

    /**
     * Disable the agent response transformer.
     *
     * @return $this
     *
     * @internal
     */
    public function skipTransformResponse(): static;

    /**
     * Transform the agent response.
     *
     * @return $this
     */
    public function transformResponseUsing(\Closure $handler): static;

    /**
     * Run the agent implementation.
     */
    public function run(?ResourceInterface $resource = null): mixed;
}
