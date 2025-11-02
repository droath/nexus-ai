<?php

declare(strict_types=1);

namespace Droath\NextusAi\Memory;

use InvalidArgumentException;
use Droath\NextusAi\Memory\Strategies\NullMemoryStrategy;
use Droath\NextusAi\Memory\Strategies\SessionMemoryStrategy;
use Droath\NextusAi\Memory\Contracts\MemoryStrategyInterface;
use Droath\NextusAi\Memory\Strategies\DatabaseMemoryStrategy;

/**
 * Factory for creating memory strategy instances.
 *
 * Provides centralized creation of memory strategies with proper configuration
 * and validation.
 */
class MemoryStrategyFactory
{
    /**
     * Define the class mappings for memory strategies.
     */
    private const array STRATEGY_CLASSES = [
        'null' => NullMemoryStrategy::class,
        'session' => SessionMemoryStrategy::class,
        'database' => DatabaseMemoryStrategy::class,
    ];

    /**
     * Initialize the memory strategy factory.
     */
    public function __construct(
        protected MemoryDefinition $definition
    ) {}

    /**
     * Creates and returns an instance of the appropriate memory strategy.
     *
     * @return MemoryStrategyInterface
     *   The instance of the memory strategy.
     */
    public function createInstance(): MemoryStrategyInterface
    {
        $strategyType = $this->definition->getType();

        if (! isset(self::STRATEGY_CLASSES[$strategyType])) {
            throw new InvalidArgumentException(
                "Unknown memory strategy: $strategyType"
            );
        }
        $classname = self::STRATEGY_CLASSES[$strategyType];

        if (! class_exists($classname)) {
            throw new InvalidArgumentException(
                "Invalid memory instance: $classname"
            );
        }

        return new $classname(
            $this->getConfigs()
        );
    }

    /**
     * Retrieves the strategy configurations.
     *
     * @return array
     *   Returns the merged configuration array of system defaults and specific
     *   definitions.
     */
    protected function getConfigs(): array
    {
        $type = $this->definition->getType();

        $systemConfig = config("nextus-ai.memory.strategies.$type", []);

        return array_replace_recursive(
            $systemConfig,
            $this->definition->getConfigs()
        );
    }
}
