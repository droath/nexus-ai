<?php

declare(strict_types=1);

namespace Droath\NextusAi\Attributes;

use Attribute;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\PluginManager\Attributes\PluginMetadata;

#[Attribute(Attribute::TARGET_CLASS)]
class AgentCoordinatorPluginMetadata extends PluginMetadata
{
    /**
     * Define the attribute constructor.
     *
     * @param string $id
     *   The plugin identifier.
     * @param string $label
     *   The plugin human-readable name.
     * @param LlmProvider $provider
     *   The LLM provider for this agent coordinator
     * @param AgentStrategy $strategy
     *   The agent coordinator strategy.
     * @param array $agents
     *   The agent plugins on which to coordinate.
     */
    public function __construct(
        string $id,
        string $label,
        protected LlmProvider $provider,
        protected AgentStrategy $strategy,
        protected array $agents = []
    ) {
        parent::__construct($id, $label);
    }
}
