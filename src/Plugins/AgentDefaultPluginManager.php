<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins;

use Droath\PluginManager\DefaultPluginManager;

abstract class AgentDefaultPluginManager extends DefaultPluginManager
{
    /**
     * Define the configuration namespace key.
     */
    abstract protected function configNamespaceKey(): string;

    /**
     * Merges the given namespace array with additional namespaces
     * defined in the configuration.
     *
     * @param array $namespaces
     *   The initial list of namespaces to be resolved.
     *
     * @return array
     *   An array of unique namespaces.
     */
    protected function resolveNamespaces(array $namespaces): array
    {
        return array_unique([
            ...$namespaces,
            ...(array) config(
                $this->configNamespaceKey(),
                []
            ),
        ]);
    }
}
