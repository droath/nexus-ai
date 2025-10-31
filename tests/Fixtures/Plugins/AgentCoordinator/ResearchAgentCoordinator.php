<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\AgentCoordinator;

use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Attributes\AgentCoordinatorPluginMetadata;
use Droath\NextusAi\Plugins\AgentCoordinator\AgentCoordinatorPlugin;

#[AgentCoordinatorPluginMetadata(
    id: 'research_agent_coordinator',
    label: 'Research Agent Coordinator',
    provider: LlmProvider::OPENAI,
    strategy: AgentStrategy::SEQUENTIAL,
    agents: ['research_agent', 'analysis_agent']
)]
class ResearchAgentCoordinator extends AgentCoordinatorPlugin {}
