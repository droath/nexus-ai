<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins;

use Droath\NextusAi\Attributes\AgentPluginMetadata;
use Droath\NextusAi\Plugins\Contracts\AgentPluginInterface;
use Droath\PluginManager\Discovery\NamespacePluginDiscovery;

class AgentPluginManager extends AgentDefaultPluginManager
{
    /**
     * {@inheritDoc}
     */
    public function __construct(array $namespaces = [])
    {
        parent::__construct(new NamespacePluginDiscovery(
            namespaces: $this->resolveNamespaces($namespaces),
            pluginInterface: AgentPluginInterface::class,
            pluginMetadataAttribute: AgentPluginMetadata::class
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function configNamespaceKey(): string
    {
        return 'nextus-ai.managers.agent.namespaces';
    }
}
