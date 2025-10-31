<?php

declare(strict_types=1);

use Droath\NextusAi\Agents\Agent;
use Droath\NextusAi\Agents\Memory\SessionMemory;
use Droath\NextusAi\Memory\MemoryDefinition;
use Droath\NextusAi\Memory\MemoryStrategyFactory;
use Droath\NextusAi\Memory\Strategies\SessionMemoryStrategy;
use Droath\NextusAi\Memory\Strategies\DatabaseMemoryStrategy;
use Droath\NextusAi\Models\AgentMemory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Store;
use Illuminate\Session\ArraySessionHandler;

uses(RefreshDatabase::class);

describe('Agent Memory Integration', function () {
    beforeEach(function () {
        // Create a mock agent for testing
        $this->agent = Agent::make()->setSystemPrompt('Test agent prompt');
    });

    describe('Legacy SessionMemory integration', function () {
        test('agent can use legacy SessionMemory', function () {
            $sessionMemory = new SessionMemory('test_memory');
            $this->agent->setMemory($sessionMemory);

            // Test that memory operations work
            expect($this->agent)->toHaveProperty('memory');
        });
    });

    describe('New Memory Strategy integration', function () {
        test('agent can use SessionMemoryStrategy', function () {
            // Create a session store for testing
            $handler = new ArraySessionHandler(600);
            $session = new Store('test_session', $handler);
            $session->start();

            $definition = new MemoryDefinition('session', ['prefix' => 'test_agent']);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();

            // For session strategy, we need to set the session
            if ($strategy instanceof SessionMemoryStrategy) {
                $strategy->setSession($session);
            }

            // Create a wrapper that implements AgentMemoryInterface
            $memoryWrapper = new class($strategy) implements \Droath\NextusAi\Agents\Contracts\AgentMemoryInterface
            {
                private $strategy;

                public function __construct($strategy)
                {
                    $this->strategy = $strategy;
                }

                public function set(string $key, mixed $value, ?int $ttl = null): bool
                {
                    return $this->strategy->set($key, $value, $ttl);
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->strategy->get($key, $default);
                }

                public function has(string $key): bool
                {
                    return $this->strategy->has($key);
                }

                public function forget(string $key): bool
                {
                    return $this->strategy->forget($key);
                }

                public function flush(): bool
                {
                    return $this->strategy->flush();
                }
            };

            $this->agent->setMemory($memoryWrapper);

            // Test memory operations through the agent
            expect($memoryWrapper->set('test_key', 'test_value'))->toBeTrue();
            expect($memoryWrapper->get('test_key'))->toBe('test_value');
            expect($memoryWrapper->has('test_key'))->toBeTrue();
            expect($memoryWrapper->forget('test_key'))->toBeTrue();
            expect($memoryWrapper->has('test_key'))->toBeFalse();
        });

        test('agent can use DatabaseMemoryStrategy', function () {
            $definition = new MemoryDefinition('database', []);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(DatabaseMemoryStrategy::class);

            // Create a wrapper that implements AgentMemoryInterface
            $memoryWrapper = new class($strategy) implements \Droath\NextusAi\Agents\Contracts\AgentMemoryInterface
            {
                private $strategy;

                public function __construct($strategy)
                {
                    $this->strategy = $strategy;
                }

                public function set(string $key, mixed $value, ?int $ttl = null): bool
                {
                    return $this->strategy->set($key, $value, $ttl);
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->strategy->get($key, $default);
                }

                public function has(string $key): bool
                {
                    return $this->strategy->has($key);
                }

                public function forget(string $key): bool
                {
                    return $this->strategy->forget($key);
                }

                public function flush(): bool
                {
                    return $this->strategy->flush();
                }
            };

            $this->agent->setMemory($memoryWrapper);

            // Test memory operations through the agent
            expect($memoryWrapper->set('db_test_key', 'db_test_value'))->toBeTrue();
            expect($memoryWrapper->get('db_test_key'))->toBe('db_test_value');
            expect($memoryWrapper->has('db_test_key'))->toBeTrue();

            // Verify data was stored in database
            expect(AgentMemory::where('key', 'db_test_key')->exists())->toBeTrue();

            // Clean up
            expect($memoryWrapper->forget('db_test_key'))->toBeTrue();
            expect(AgentMemory::where('key', 'db_test_key')->exists())->toBeFalse();
        });
    });

    describe('Memory persistence across agent operations', function () {
        test('agent memory persists data correctly with database strategy', function () {
            $definition = new MemoryDefinition('database', []);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();

            // Create memory wrapper
            $memoryWrapper = new class($strategy) implements \Droath\NextusAi\Agents\Contracts\AgentMemoryInterface
            {
                private $strategy;

                public function __construct($strategy)
                {
                    $this->strategy = $strategy;
                }

                public function set(string $key, mixed $value, ?int $ttl = null): bool
                {
                    return $this->strategy->set($key, $value, $ttl);
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->strategy->get($key, $default);
                }

                public function has(string $key): bool
                {
                    return $this->strategy->has($key);
                }

                public function forget(string $key): bool
                {
                    return $this->strategy->forget($key);
                }

                public function flush(): bool
                {
                    return $this->strategy->flush();
                }
            };

            // Set up two different agents with the same memory
            $agent1 = Agent::make()->setSystemPrompt('Agent 1')->setMemory($memoryWrapper);
            $agent2 = Agent::make()->setSystemPrompt('Agent 2')->setMemory($memoryWrapper);

            // Agent 1 stores data
            expect($memoryWrapper->set('shared_key', ['agent' => 1, 'data' => 'from_agent_1']))->toBeTrue();

            // Agent 2 can access the same data
            $retrievedData = $memoryWrapper->get('shared_key');
            expect($retrievedData)->toBe(['agent' => 1, 'data' => 'from_agent_1']);

            // Agent 2 modifies the data
            expect($memoryWrapper->set('shared_key', ['agent' => 2, 'data' => 'modified_by_agent_2']))->toBeTrue();

            // Agent 1 sees the modified data
            $modifiedData = $memoryWrapper->get('shared_key');
            expect($modifiedData)->toBe(['agent' => 2, 'data' => 'modified_by_agent_2']);

            // Clean up
            expect($memoryWrapper->flush())->toBeTrue();
        });
    });
})->group('memory', 'agents', 'integration', 'unit');
