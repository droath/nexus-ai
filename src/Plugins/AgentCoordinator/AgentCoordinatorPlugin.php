<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins\AgentCoordinator;

use Illuminate\Support\Facades\Log;
use Droath\PluginManager\Plugin\PluginBase;
use Droath\NextusAi\Agents\AgentCoordinator;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Plugins\AgentPluginManager;
use Droath\NextusAi\Plugins\Concerns\HasLlmResource;
use Droath\NextusAi\Plugins\Contracts\AgentPluginInterface;
use Droath\NextusAi\Agents\ValueObject\AgentCoordinatorResponse;
use Droath\NextusAi\Plugins\Contracts\AgentCoordinatorPluginInterface;

abstract class AgentCoordinatorPlugin extends PluginBase implements AgentCoordinatorPluginInterface
{
    use HasLlmResource;

    /**
     * {@inheritDoc}
     */
    public function respond(
        string|array $input = []
    ): AgentCoordinatorResponse {
        try {
            if ($agents = $this->resolveAgents()) {
                $coordinator = AgentCoordinator::make(
                    $input,
                    $agents,
                    $this->strategy()
                );

                if ($format = $this->responseFormat()) {
                    $coordinator->setResponseFormat($format);
                }

                return $coordinator->run($this->resource());
            }
            Log::warning('No agents available for coordination', [
                'plugin_id' => $this->getPluginId(),
            ]);
        } catch (\Exception $exception) {
            Log::error('Agent coordination failed', [
                'error' => $exception->getMessage(),
                'plugin_id' => $this->getPluginId(),
            ]);
        }

        return AgentCoordinatorResponse::make(
            agents: [],
            strategy: $this->strategy(),
            resource: $this->resource(),
            coordinatorResponse: null
        );
    }

    /**
     * Define the agent coordinator agents.
     */
    protected function agents(): array
    {
        return [];
    }

    /**
     * Define the agent coordinator response format.
     */
    protected function responseFormat(): array
    {
        return [];
    }

    /**
     * Load the plugin agents for the agent coordinator.
     */
    private function loadAgents(): array
    {
        $agentIds = $this->pluginDefinition['agents'] ?? [];

        if (empty($agentIds)) {
            return [];
        }
        $agents = [];
        $manager = app(AgentPluginManager::class);

        foreach ($agentIds as $agentId) {
            try {
                $pluginInstance = $manager->createInstance($agentId);

                if (! $pluginInstance instanceof AgentPluginInterface) {
                    continue;
                }
                $agents[] = $pluginInstance->createInstance();
            } catch (\Exception $exception) {
                Log::error('Failed to load agent', [
                    'agent_id' => $agentId,
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }
        }

        return $agents;
    }

    /**
     * Resolve the agent coordinator agents.
     */
    private function resolveAgents(): array
    {
        return array_merge($this->agents(), $this->loadAgents());
    }

    /**
     * Get the agent coordinator strategy.
     */
    private function strategy(): AgentStrategy
    {
        return $this->pluginDefinition['strategy'] ?? AgentStrategy::SEQUENTIAL;
    }
}
