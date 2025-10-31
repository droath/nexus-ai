<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins;

use Droath\NextusAi\Attributes\AgentToolPluginMetadata;
use Droath\NextusAi\Plugins\Contracts\AgentToolPluginInterface;
use Droath\PluginManager\Discovery\NamespacePluginDiscovery;

class AgentToolPluginManager extends AgentDefaultPluginManager
{
    /**
     * {@inheritDoc}
     */
    public function __construct(array $namespaces = [])
    {
        parent::__construct(new NamespacePluginDiscovery(
            namespaces: $this->resolveNamespaces($namespaces),
            pluginInterface: AgentToolPluginInterface::class,
            pluginMetadataAttribute: AgentToolPluginMetadata::class
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function configNamespaceKey(): string
    {
        return 'nextus-ai.managers.agent_tool.namespaces';
    }
}
