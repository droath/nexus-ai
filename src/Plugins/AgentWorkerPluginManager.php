<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins;

use Droath\PluginManager\DefaultPluginManager;
use Droath\NextusAi\Attributes\AgentWorkerPluginMetadata;
use Droath\PluginManager\Discovery\NamespacePluginDiscovery;
use Droath\NextusAi\Plugins\Contracts\AgentWorkerPluginInterface;

/**
 * Define the agent worker plugin manager.
 *
 * @deperecated
 */
class AgentWorkerPluginManager extends DefaultPluginManager
{
    public function __construct()
    {
        parent::__construct(new NamespacePluginDiscovery(
            namespaces: ['App\Plugins'],
            pluginInterface: AgentWorkerPluginInterface::class,
            pluginMetadataAttribute: AgentWorkerPluginMetadata::class
        ));
    }
}
