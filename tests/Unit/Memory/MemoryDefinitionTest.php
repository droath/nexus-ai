<?php

declare(strict_types=1);

use Droath\NextusAi\Memory\MemoryDefinition;
use Illuminate\Contracts\Support\Arrayable;

describe('MemoryDefinition', function () {
    test('can be instantiated with valid type', function () {
        $definition = new MemoryDefinition('session');

        expect($definition)->toBeInstanceOf(MemoryDefinition::class)
            ->and($definition->getType())->toBe('session')
            ->and($definition->getConfigs())->toBe([]);
    });

    test('can be instantiated with type and configs', function () {
        $configs = ['prefix' => 'test_memory', 'ttl' => 3600];
        $definition = new MemoryDefinition('session', $configs);

        expect($definition->getType())->toBe('session')
            ->and($definition->getConfigs())->toBe($configs);
    });

    test('validates strategy type on construction', function () {
        expect(fn () => new MemoryDefinition('invalid_type'))
            ->toThrow(InvalidArgumentException::class, 'Invalid memory strategy type \'invalid_type\'');
    });

    test('implements Arrayable interface', function () {
        $definition = new MemoryDefinition('database');

        expect($definition)->toBeInstanceOf(Arrayable::class);
    });

    test('is readonly class', function () {
        $reflection = new ReflectionClass(MemoryDefinition::class);

        expect($reflection->isReadOnly())->toBeTrue();
    });

    describe('fromArray static constructor', function () {
        test('creates instance from array with type only', function () {
            $data = ['type' => 'null'];
            $definition = MemoryDefinition::fromArray($data);

            expect($definition->getType())->toBe('null')
                ->and($definition->getConfigs())->toBe([]);
        });

        test('creates instance from array with type and configs', function () {
            $data = [
                'type' => 'database',
                'configs' => ['table' => 'agent_memory', 'connection' => 'mysql'],
            ];
            $definition = MemoryDefinition::fromArray($data);

            expect($definition->getType())->toBe('database')
                ->and($definition->getConfigs())->toBe([
                    'table' => 'agent_memory',
                    'connection' => 'mysql',
                ]);
        });

        test('throws exception when type is missing', function () {
            expect(fn () => MemoryDefinition::fromArray(['configs' => []]))
                ->toThrow(InvalidArgumentException::class, 'Memory definition must include a type');
        });

        test('validates type from array', function () {
            expect(fn () => MemoryDefinition::fromArray(['type' => 'invalid']))
                ->toThrow(InvalidArgumentException::class, 'Invalid memory strategy type');
        });
    });

    describe('toArray method', function () {
        test('returns array representation with type only', function () {
            $definition = new MemoryDefinition('session');

            expect($definition->toArray())->toBe([
                'type' => 'session',
                'configs' => [],
            ]);
        });

        test('returns array representation with type and configs', function () {
            $configs = ['prefix' => 'custom', 'enabled' => true];
            $definition = new MemoryDefinition('session', $configs);

            expect($definition->toArray())->toBe([
                'type' => 'session',
                'configs' => $configs,
            ]);
        });
    });

    describe('configuration methods', function () {
        beforeEach(function () {
            $this->configs = [
                'prefix' => 'agent_memory',
                'ttl' => 3600,
                'enabled' => true,
                'nested' => ['option' => 'value'],
            ];
            $this->definition = new MemoryDefinition('session', $this->configs);
        });

        test('getConfig returns specific configuration value', function () {
            expect($this->definition->getConfig('prefix'))->toBe('agent_memory')
                ->and($this->definition->getConfig('ttl'))->toBe(3600)
                ->and($this->definition->getConfig('enabled'))->toBeTrue()
                ->and($this->definition->getConfig('nested'))->toBe(['option' => 'value']);
        });

        test('getConfig returns default when key does not exist', function () {
            expect($this->definition->getConfig('nonexistent'))->toBeNull()
                ->and($this->definition->getConfig('missing', 'default'))->toBe('default')
                ->and($this->definition->getConfig('absent', 123))->toBe(123);
        });

        test('hasConfig checks if configuration exists', function () {
            expect($this->definition->hasConfig('prefix'))->toBeTrue()
                ->and($this->definition->hasConfig('ttl'))->toBeTrue()
                ->and($this->definition->hasConfig('enabled'))->toBeTrue()
                ->and($this->definition->hasConfig('nested'))->toBeTrue()
                ->and($this->definition->hasConfig('nonexistent'))->toBeFalse();
        });

        test('hasConfig returns true for null values', function () {
            $definition = new MemoryDefinition('session', ['nullable' => null]);

            expect($definition->hasConfig('nullable'))->toBeTrue()
                ->and($definition->getConfig('nullable'))->toBeNull();
        });
    });

    describe('valid strategy types', function () {
        test('accepts all valid strategy types', function () {
            foreach (MemoryDefinition::VALID_TYPES as $type) {
                $definition = new MemoryDefinition($type);
                expect($definition->getType())->toBe($type);
            }
        });

        test('VALID_TYPES constant contains expected strategies', function () {
            expect(MemoryDefinition::VALID_TYPES)->toBe(['session', 'database', 'null'])
                ->and(MemoryDefinition::VALID_TYPES)->toHaveCount(3);
        });

        test('rejects invalid strategy types', function () {
            $invalidTypes = ['redis', 'file', 'cache', 'memory', ''];

            foreach ($invalidTypes as $type) {
                expect(fn () => new MemoryDefinition($type))
                    ->toThrow(InvalidArgumentException::class);
            }
        });
    });

    describe('immutability', function () {
        test('constructor parameters are protected', function () {
            $configs = ['original' => 'value'];
            $definition = new MemoryDefinition('session', $configs);

            // Modify original array - should not affect definition
            $configs['modified'] = 'new_value';

            expect($definition->getConfigs())->toBe(['original' => 'value'])
                ->and($definition->hasConfig('modified'))->toBeFalse();
        });

        test('getConfigs returns array copy', function () {
            $definition = new MemoryDefinition('session', ['test' => 'value']);
            $configs = $definition->getConfigs();

            // Modify returned array - should not affect internal state
            $configs['new'] = 'added';

            expect($definition->getConfigs())->toBe(['test' => 'value'])
                ->and($definition->hasConfig('new'))->toBeFalse();
        });
    });

    describe('edge cases', function () {
        test('handles empty configs array', function () {
            $definition = new MemoryDefinition('null', []);

            expect($definition->getConfigs())->toBe([])
                ->and($definition->hasConfig('anything'))->toBeFalse()
                ->and($definition->getConfig('missing', 'default'))->toBe('default');
        });

        test('handles complex nested configurations', function () {
            $complexConfig = [
                'database' => [
                    'connections' => [
                        'mysql' => ['host' => 'localhost'],
                        'sqlite' => ['database' => ':memory:'],
                    ],
                ],
                'options' => [1, 2, 3, 'string', true, null],
            ];

            $definition = new MemoryDefinition('database', $complexConfig);

            expect($definition->getConfig('database'))->toBe($complexConfig['database'])
                ->and($definition->getConfig('options'))->toBe($complexConfig['options']);
        });

        test('fromArray handles missing configs key', function () {
            $definition = MemoryDefinition::fromArray(['type' => 'session']);

            expect($definition->getType())->toBe('session')
                ->and($definition->getConfigs())->toBe([]);
        });
    });
})->group('memory', 'definition', 'unit');
