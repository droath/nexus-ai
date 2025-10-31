<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins\AgentTool;

use Droath\NextusAi\Tools\Tool;
use Droath\PluginManager\Plugin\PluginBase;
use Droath\NextusAi\Plugins\Contracts\AgentToolPluginInterface;

/**
 * Define the agent tool plugin base class.
 */
abstract class AgentToolPlugin extends PluginBase implements AgentToolPluginInterface
{
    protected Tool $tool;

    public function __construct(
        array $configuration,
        array $pluginDefinition
    ) {
        parent::__construct($configuration, $pluginDefinition);
    }

    /**
     * {@inheritDoc}
     */
    public function definition(): Tool
    {
        return Tool::make($this->getPluginId())
            ->using(function (array $arguments) {
                return $this->execute($arguments);
            })->withProperties($this->properties());
    }

    /**
     * Define the tool properties.
     */
    abstract protected function properties(): array;

    /**
     * Define the tool execution.
     */
    abstract protected function execute(array $arguments): mixed;
}
