<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\AgentCoordinator;

use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Attributes\AgentCoordinatorPluginMetadata;
use Droath\NextusAi\Plugins\AgentCoordinator\AgentCoordinatorPlugin;

#[AgentCoordinatorPluginMetadata(
    id: 'reporter_agent_coordinator',
    label: 'Reporter Agent Coordinator',
    provider: LlmProvider::OPENAI,
    strategy: AgentStrategy::ROUTER,
    agents: ['sport_agent', 'weather_agent']
)]
class ReporterAgentCoordinator extends AgentCoordinatorPlugin {}
