<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins\Contracts;

use Droath\NextusAi\Agents\Agent;

/**
 * Define the agent plugin interface.
 */
interface AgentPluginInterface
{
    /**
     * Executes the agent and returns its response.
     *
     * @return mixed
     *   The agents' response.
     */
    public function run(): mixed;

    /**
     * Create an agent instance object.
     */
    public function createInstance(string|array $input = []): Agent;
}
