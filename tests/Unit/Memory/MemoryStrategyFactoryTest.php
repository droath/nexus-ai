<?php

declare(strict_types=1);

use Droath\NextusAi\Memory\MemoryStrategyFactory;
use Droath\NextusAi\Memory\MemoryDefinition;
use Droath\NextusAi\Memory\Contracts\MemoryStrategyInterface;
use Droath\NextusAi\Memory\Strategies\SessionMemoryStrategy;
use Droath\NextusAi\Memory\Strategies\DatabaseMemoryStrategy;
use Droath\NextusAi\Memory\Strategies\NullMemoryStrategy;

describe('MemoryStrategyFactory', function () {
    describe('constructor and basic functionality', function () {
        test('can be instantiated with MemoryDefinition', function () {
            $definition = new MemoryDefinition('session');
            $factory = new MemoryStrategyFactory($definition);

            expect($factory)->toBeInstanceOf(MemoryStrategyFactory::class);
        });

        test('accepts MemoryDefinition with configs', function () {
            $definition = new MemoryDefinition('session', ['prefix' => 'test']);
            $factory = new MemoryStrategyFactory($definition);

            expect($factory)->toBeInstanceOf(MemoryStrategyFactory::class);
        });
    });

    describe('createInstance method', function () {
        test('creates SessionMemoryStrategy for session type', function () {
            $definition = new MemoryDefinition('session', ['prefix' => 'test_session']);
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(SessionMemoryStrategy::class)
                ->and($strategy)->toBeInstanceOf(MemoryStrategyInterface::class);
        });

        test('creates DatabaseMemoryStrategy for database type', function () {
            $definition = new MemoryDefinition('database', ['table' => 'agent_memory']);
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(DatabaseMemoryStrategy::class)
                ->and($strategy)->toBeInstanceOf(MemoryStrategyInterface::class);
        });

        test('creates NullMemoryStrategy for null type', function () {
            $definition = new MemoryDefinition('null');
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(NullMemoryStrategy::class)
                ->and($strategy)->toBeInstanceOf(MemoryStrategyInterface::class);
        });

        test('passes configuration to strategy instances', function () {
            $configs = ['prefix' => 'custom_prefix', 'ttl' => 7200];
            $definition = new MemoryDefinition('session', $configs);
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(SessionMemoryStrategy::class);
        });

        test('creates instances with empty configs', function () {
            $definition = new MemoryDefinition('null');
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(NullMemoryStrategy::class);
        });
    });

    describe('error handling', function () {
        test('throws exception for unknown strategy type', function () {
            // We need to create an invalid definition by bypassing validation
            $reflection = new ReflectionClass(MemoryDefinition::class);
            $typeProperty = $reflection->getProperty('type');
            $typeProperty->setAccessible(true);

            $definition = $reflection->newInstanceWithoutConstructor();
            $typeProperty->setValue($definition, 'unknown_strategy');

            $factory = new MemoryStrategyFactory($definition);

            expect(fn () => $factory->createInstance())
                ->toThrow(InvalidArgumentException::class, 'Unknown memory strategy: unknown_strategy');
        });
    });

    describe('strategy class mappings', function () {
        test('has correct strategy class mappings', function () {
            $reflection = new ReflectionClass(MemoryStrategyFactory::class);
            $strategyClasses = $reflection->getConstant('STRATEGY_CLASSES');

            expect($strategyClasses)->toBe([
                'null' => NullMemoryStrategy::class,
                'session' => SessionMemoryStrategy::class,
                'database' => DatabaseMemoryStrategy::class,
            ]);
        });

        test('all mapped classes implement MemoryStrategyInterface', function () {
            $reflection = new ReflectionClass(MemoryStrategyFactory::class);
            $strategyClasses = $reflection->getConstant('STRATEGY_CLASSES');

            foreach ($strategyClasses as $type => $className) {
                $classReflection = new ReflectionClass($className);
                expect($classReflection->implementsInterface(MemoryStrategyInterface::class))
                    ->toBeTrue("$className should implement MemoryStrategyInterface");
            }
        });

        test('creates different instances for each type', function () {
            $types = ['session', 'database', 'null'];
            $instances = [];

            foreach ($types as $type) {
                $definition = new MemoryDefinition($type);
                $factory = new MemoryStrategyFactory($definition);
                $instances[$type] = $factory->createInstance();
            }

            expect($instances['session'])->toBeInstanceOf(SessionMemoryStrategy::class)
                ->and($instances['database'])->toBeInstanceOf(DatabaseMemoryStrategy::class)
                ->and($instances['null'])->toBeInstanceOf(NullMemoryStrategy::class);

            // Ensure they are different instances
            expect($instances['session'])->not->toBe($instances['database'])
                ->and($instances['database'])->not->toBe($instances['null'])
                ->and($instances['session'])->not->toBe($instances['null']);
        });
    });

    describe('integration with different strategy configurations', function () {
        test('creates session strategy with custom configuration', function () {
            $configs = [
                'prefix' => 'custom_agent_memory',
                'enabled' => true,
            ];
            $definition = new MemoryDefinition('session', $configs);
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(SessionMemoryStrategy::class);
        });

        test('creates database strategy with custom configuration', function () {
            $configs = [
                'table' => 'custom_memory_table',
                'connection' => 'mysql',
                'cleanup_probability' => 50,
            ];
            $definition = new MemoryDefinition('database', $configs);
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(DatabaseMemoryStrategy::class);
        });

        test('creates null strategy with configuration', function () {
            $configs = ['enabled' => false, 'debug' => true];
            $definition = new MemoryDefinition('null', $configs);
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(NullMemoryStrategy::class);
        });
    });

    describe('factory behavior consistency', function () {
        test('creates new instances on each call', function () {
            $definition = new MemoryDefinition('session');
            $factory = new MemoryStrategyFactory($definition);

            $instance1 = $factory->createInstance();
            $instance2 = $factory->createInstance();

            expect($instance1)->toBeInstanceOf(SessionMemoryStrategy::class)
                ->and($instance2)->toBeInstanceOf(SessionMemoryStrategy::class)
                ->and($instance1)->not->toBe($instance2); // Different instances
        });

        test('uses same configuration for multiple instances', function () {
            $configs = ['prefix' => 'consistent_prefix'];
            $definition = new MemoryDefinition('session', $configs);
            $factory = new MemoryStrategyFactory($definition);

            $instance1 = $factory->createInstance();
            $instance2 = $factory->createInstance();

            expect($instance1)->toBeInstanceOf(SessionMemoryStrategy::class)
                ->and($instance2)->toBeInstanceOf(SessionMemoryStrategy::class);
        });
    });

    describe('edge cases and robustness', function () {
        test('handles complex nested configurations', function () {
            $complexConfigs = [
                'database' => [
                    'connections' => [
                        'primary' => ['host' => 'localhost'],
                        'backup' => ['host' => 'backup.example.com'],
                    ],
                ],
                'options' => [1, 2, 3, 'test', true, null],
            ];

            $definition = new MemoryDefinition('database', $complexConfigs);
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(DatabaseMemoryStrategy::class);
        });

        test('handles empty configuration gracefully', function () {
            $definition = new MemoryDefinition('null', []);
            $factory = new MemoryStrategyFactory($definition);

            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(NullMemoryStrategy::class);
        });
    });
})->group('memory', 'factory', 'unit');
