<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins\Contracts;

use Droath\PluginManager\Contracts\PluginInterface;
use Droath\NextusAi\Agents\ValueObject\AgentCoordinatorResponse;

/**
 * Define the agent worker plugin interface.
 *
 * @deperecated
 */
interface AgentWorkerPluginInterface extends PluginInterface
{
    /**
     * Invoke the agent to respond.
     *
     * @param string|array $messages
     *   An array of messages to send to the agent.
     * @param array $tools
     *   An array of tools to send to the agent.
     *
     * @return array|AgentCoordinatorResponse
     *   An array of the agent responses.
     */
    public function respond(
        string|array $messages = [],
        array $tools = []
    ): array|AgentCoordinatorResponse;
}
