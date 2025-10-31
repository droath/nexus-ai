<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins\Contracts;

use Droath\NextusAi\Tools\Tool;

/**
 * Define the agent tool plugin interface.
 */
interface AgentToolPluginInterface
{
    /**
     * The tool plugin definition.
     */
    public function definition(): Tool;
}
