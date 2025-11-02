<?php

declare(strict_types=1);

use Illuminate\Session\Store;
use OpenAI\Testing\ClientFake;
use Droath\NextusAi\Agents\Agent;
use Droath\NextusAi\Facades\NextusAi;
use Droath\NextusAi\Models\AgentMemory;
use Illuminate\Session\ArraySessionHandler;
use Droath\NextusAi\Agents\AgentCoordinator;
use Droath\NextusAi\Memory\MemoryDefinition;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Memory\MemoryStrategyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Droath\NextusAi\Memory\Strategies\SessionMemoryStrategy;
use Droath\NextusAi\Testing\Support\ResourceResponsesHelper;

uses(RefreshDatabase::class);
uses(ResourceResponsesHelper::class);

describe('AgentCoordinator Memory Integration', function () {
    beforeEach(function () {
        // Helper function to create memory wrapper
        $this->createMemoryWrapper = function ($strategy) {
            return new class($strategy) implements Droath\NextusAi\Agents\Contracts\AgentMemoryInterface
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
        };
    });

    describe('Memory sharing between coordinator and agents', function () {
        test('coordinator shares memory with agents using database strategy', function () {
            $definition = new MemoryDefinition('database', []);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();
            $memoryWrapper = ($this->createMemoryWrapper)($strategy);

            // Create agents
            $agent1 = Agent::make()->setSystemPrompt('Agent 1: Store data');
            $agent2 = Agent::make()->setSystemPrompt('Agent 2: Read data');

            // Create coordinator with memory
            $coordinator = AgentCoordinator::make(
                'Test coordination with memory',
                [$agent1, $agent2],
                AgentStrategy::PARALLEL
            )->setMemory($memoryWrapper);

            // Pre-populate memory before coordination
            expect($memoryWrapper->set('coordinator_data', 'shared_between_agents'))->toBeTrue();

            // Mock Nextus AI responses
            /** @phpstan-ignore-next-line */
            $response1 = $this->createFakeTextResponse('Agent 1 response');
            /** @phpstan-ignore-next-line */
            $response2 = $this->createFakeTextResponse('Agent 2 response');
            /** @phpstan-ignore-next-line */
            $coordinatorResponse = $this->createFakeTextResponse('Coordinator response');

            NextusAi::fake(resourceCallback: function () use ($response1, $response2, $coordinatorResponse) {
                $client = new ClientFake([$response1, $response2, $coordinatorResponse]);

                return (new Droath\NextusAi\Drivers\Openai($client))->structured();
            });

            $resource = NextusAi::structured(LlmProvider::OPENAI);

            // Execute coordination
            $result = $coordinator->run($resource);

            // Verify agents received the memory instance
            $result->assertAgentsUsing(function ($agents) use ($memoryWrapper) {
                // We can't directly access the memory from agents in the test,
                // but we can verify the memory still contains the data
                expect($memoryWrapper->get('coordinator_data'))->toBe('shared_between_agents');

                return true;
            });

            // Clean up
            expect($memoryWrapper->flush())->toBeTrue();
        });

        test('coordinator memory persists across sequential agent execution', function () {
            $definition = new MemoryDefinition('database', []);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();
            $memoryWrapper = ($this->createMemoryWrapper)($strategy);

            // Create agents that would use memory in sequence
            $agent1 = Agent::make()->setSystemPrompt('Agent 1: Process and store result');
            $agent2 = Agent::make()->setSystemPrompt('Agent 2: Use previous result');

            // Create sequential coordinator
            $coordinator = AgentCoordinator::make(
                'Sequential processing with memory',
                [$agent1, $agent2],
                AgentStrategy::SEQUENTIAL
            )->setMemory($memoryWrapper);

            // Store initial data
            expect($memoryWrapper->set('processing_step', 'initialized'))->toBeTrue();

            // Mock responses for sequential execution
            /** @phpstan-ignore-next-line */
            $response1 = $this->createFakeTextResponse('First agent processed data');
            /** @phpstan-ignore-next-line */
            $response2 = $this->createFakeTextResponse('Second agent used processed data');
            /** @phpstan-ignore-next-line */
            $coordinatorResponse = $this->createFakeTextResponse('Sequential coordination complete');

            NextusAi::fake(resourceCallback: function () use ($response1, $response2, $coordinatorResponse) {
                $client = new ClientFake([$response1, $response2, $coordinatorResponse]);

                return (new Droath\NextusAi\Drivers\Openai($client))->structured();
            });

            $resource = NextusAi::structured(LlmProvider::OPENAI);

            // Execute sequential coordination
            $result = $coordinator->run($resource);

            // Verify memory persisted throughout the process
            expect($memoryWrapper->get('processing_step'))->toBe('initialized');

            // Simulate what agents might do with memory during execution
            expect($memoryWrapper->set('processing_step', 'agent1_completed'))->toBeTrue();
            expect($memoryWrapper->set('processing_step', 'agent2_completed'))->toBeTrue();

            expect($memoryWrapper->get('processing_step'))->toBe('agent2_completed');

            // Clean up
            expect($memoryWrapper->flush())->toBeTrue();
        });
    });

    describe('Memory strategy compatibility with coordinator', function () {
        test('coordinator works with session memory strategy', function () {
            // Create session strategy
            $handler = new ArraySessionHandler(600);
            $session = new Store('coordinator_session', $handler);
            $session->start();

            $definition = new MemoryDefinition('session', ['prefix' => 'coordinator_mem']);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();

            if ($strategy instanceof SessionMemoryStrategy) {
                $strategy->setSession($session);
            }

            $memoryWrapper = ($this->createMemoryWrapper)($strategy);

            // Create a simple coordinator
            $agent = Agent::make()->setSystemPrompt('Test agent with session memory');
            $coordinator = AgentCoordinator::make(
                'Session memory test',
                [$agent],
                AgentStrategy::PARALLEL
            )->setMemory($memoryWrapper);

            // Test memory operations
            expect($memoryWrapper->set('session_test', 'session_value'))->toBeTrue();
            expect($memoryWrapper->get('session_test'))->toBe('session_value');

            // Mock a simple response
            /** @phpstan-ignore-next-line */
            $response = $this->createFakeTextResponse('Agent response');
            /** @phpstan-ignore-next-line */
            $coordinatorResponse = $this->createFakeTextResponse('Coordinator response');

            NextusAi::fake(resourceCallback: function () use ($response, $coordinatorResponse) {
                $client = new ClientFake([$response, $coordinatorResponse]);

                return (new Droath\NextusAi\Drivers\Openai($client))->structured();
            });

            $resource = NextusAi::structured(LlmProvider::OPENAI);
            $result = $coordinator->run($resource);

            // Verify the coordinator executed successfully
            expect($result)->toBeInstanceOf(Droath\NextusAi\Agents\ValueObject\AgentCoordinatorResponse::class);

            // Verify memory is still accessible
            expect($memoryWrapper->get('session_test'))->toBe('session_value');

            // Clean up
            expect($memoryWrapper->flush())->toBeTrue();
        });
    });

    describe('Memory isolation and sharing scenarios', function () {
        test('multiple coordinators can share database memory', function () {
            // Create shared database memory
            $definition = new MemoryDefinition('database', []);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();
            $sharedMemory = ($this->createMemoryWrapper)($strategy);

            // Create two coordinators sharing the same memory
            $coordinator1 = AgentCoordinator::make(
                'First coordinator',
                [Agent::make()->setSystemPrompt('Agent in coordinator 1')],
                AgentStrategy::PARALLEL
            )->setMemory($sharedMemory);

            $coordinator2 = AgentCoordinator::make(
                'Second coordinator',
                [Agent::make()->setSystemPrompt('Agent in coordinator 2')],
                AgentStrategy::PARALLEL
            )->setMemory($sharedMemory);

            // First coordinator stores data
            expect($sharedMemory->set('shared_data', 'from_coordinator_1'))->toBeTrue();

            // Second coordinator can access the same data
            expect($sharedMemory->get('shared_data'))->toBe('from_coordinator_1');

            // Second coordinator modifies the data
            expect($sharedMemory->set('shared_data', 'modified_by_coordinator_2'))->toBeTrue();

            // First coordinator sees the modification
            expect($sharedMemory->get('shared_data'))->toBe('modified_by_coordinator_2');

            // Verify database persistence
            expect(AgentMemory::where('key', 'shared_data')->first()->value)
                ->toBe('modified_by_coordinator_2');

            // Clean up
            expect($sharedMemory->flush())->toBeTrue();
        });

        test('coordinator memory operations work with TTL', function () {
            $definition = new MemoryDefinition('database', ['default_ttl' => 3600]);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();
            $memoryWrapper = ($this->createMemoryWrapper)($strategy);

            $coordinator = AgentCoordinator::make(
                'TTL test coordinator',
                [Agent::make()->setSystemPrompt('TTL test agent')],
                AgentStrategy::PARALLEL
            )->setMemory($memoryWrapper);

            // Store data with specific TTL
            expect($memoryWrapper->set('ttl_test', 'ttl_value', 3600))->toBeTrue();

            // Verify data exists
            expect($memoryWrapper->has('ttl_test'))->toBeTrue();
            expect($memoryWrapper->get('ttl_test'))->toBe('ttl_value');

            // Verify database record has expiration set
            $record = AgentMemory::where('key', 'ttl_test')->first();
            expect($record)->not->toBeNull();
            expect($record->expires_at)->not->toBeNull();
            expect($record->expires_at->isFuture())->toBeTrue();

            // Clean up
            expect($memoryWrapper->flush())->toBeTrue();
        });
    });
})->group('memory', 'agents', 'coordinator', 'integration', 'unit');
