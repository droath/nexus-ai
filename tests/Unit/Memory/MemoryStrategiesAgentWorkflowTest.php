<?php

declare(strict_types=1);

use Illuminate\Session\Store;
use Droath\NextusAi\Agents\Agent;
use Droath\NextusAi\Models\AgentMemory;
use Droath\NextusAi\Services\MemoryManager;
use Illuminate\Session\ArraySessionHandler;
use Droath\NextusAi\Agents\AgentCoordinator;
use Droath\NextusAi\Memory\MemoryDefinition;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Memory\MemoryStrategyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Droath\NextusAi\Memory\Strategies\NullMemoryStrategy;
use Droath\NextusAi\Memory\Strategies\SessionMemoryStrategy;
use Droath\NextusAi\Memory\Strategies\DatabaseMemoryStrategy;

uses(RefreshDatabase::class);

describe('Memory Strategies in Agent Workflows', function () {
    beforeEach(function () {
        // Helper to create memory wrapper for AgentMemoryInterface compatibility
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

    describe('All memory strategies work with agent workflow', function () {
        test('SessionMemoryStrategy integrates with agent workflow', function () {
            // Create session strategy
            $handler = new ArraySessionHandler(600);
            $session = new Store('workflow_session', $handler);
            $session->start();

            $definition = new MemoryDefinition('session', ['prefix' => 'agent_workflow']);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(SessionMemoryStrategy::class);

            if ($strategy instanceof SessionMemoryStrategy) {
                $strategy->setSession($session);
            }

            $memoryWrapper = ($this->createMemoryWrapper)($strategy);

            // Create agent and set memory
            $agent = Agent::make()->setSystemPrompt('Agent with session memory');
            $agent->setMemory($memoryWrapper);

            // Test workflow: store data, verify persistence
            expect($memoryWrapper->set('workflow_step_1', 'session_data'))->toBeTrue();
            expect($memoryWrapper->get('workflow_step_1'))->toBe('session_data');

            // Simulate agent processing workflow
            expect($memoryWrapper->set('workflow_step_2', ['processed' => true, 'by' => 'session_agent']))->toBeTrue();
            $processedData = $memoryWrapper->get('workflow_step_2');
            expect($processedData)->toBe(['processed' => true, 'by' => 'session_agent']);

            // Verify session contains the data
            expect($session->get('agent_workflow.workflow_step_1'))->toBe('session_data');
            expect($session->get('agent_workflow.workflow_step_2'))->toBe(['processed' => true, 'by' => 'session_agent']);

            // Clean up
            expect($memoryWrapper->flush())->toBeTrue();
        });

        test('DatabaseMemoryStrategy integrates with agent workflow', function () {
            $definition = new MemoryDefinition('database', ['default_ttl' => 3600]);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(DatabaseMemoryStrategy::class);

            $memoryWrapper = ($this->createMemoryWrapper)($strategy);

            // Create agent with database memory
            $agent = Agent::make()->setSystemPrompt('Agent with database memory');
            $agent->setMemory($memoryWrapper);

            // Test workflow with persistent data
            expect($memoryWrapper->set('persistent_workflow', 'database_data'))->toBeTrue();
            expect($memoryWrapper->get('persistent_workflow'))->toBe('database_data');

            // Verify data persisted to database
            expect(AgentMemory::where('key', 'persistent_workflow')->exists())->toBeTrue();
            $record = AgentMemory::where('key', 'persistent_workflow')->first();
            expect($record->value)->toBe('database_data');
            expect($record->expires_at->isFuture())->toBeTrue();

            // Simulate complex workflow data
            $complexData = [
                'workflow_id' => 'wf_123',
                'steps' => ['analyze', 'process', 'respond'],
                'current_step' => 'process',
                'metadata' => ['agent_id' => 'agent_001', 'timestamp' => now()->toISOString()],
            ];

            expect($memoryWrapper->set('complex_workflow', $complexData))->toBeTrue();
            $retrieved = $memoryWrapper->get('complex_workflow');
            expect($retrieved)->toBe($complexData);

            // Clean up
            expect($memoryWrapper->flush())->toBeTrue();
        });

        test('NullMemoryStrategy integrates with agent workflow', function () {
            $definition = new MemoryDefinition('null', []);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();

            expect($strategy)->toBeInstanceOf(NullMemoryStrategy::class);

            $memoryWrapper = ($this->createMemoryWrapper)($strategy);

            // Create agent with null memory (for testing/performance scenarios)
            $agent = Agent::make()->setSystemPrompt('Agent with null memory');
            $agent->setMemory($memoryWrapper);

            // Test that null memory operations succeed but don't persist
            expect($memoryWrapper->set('null_test', 'no_storage'))->toBeTrue();
            expect($memoryWrapper->get('null_test'))->toBeNull(); // Always returns null/default
            expect($memoryWrapper->has('null_test'))->toBeFalse(); // Never has data

            // Verify no database records created
            expect(AgentMemory::where('key', 'null_test')->exists())->toBeFalse();

            // Flush should succeed
            expect($memoryWrapper->flush())->toBeTrue();
        });
    });

    describe('Memory Manager integration with agent workflows', function () {
        test('MemoryManager can create strategies for agent use', function () {
            $manager = new MemoryManager();

            // Test creating different strategies through manager
            $sessionStrategy = $manager->createSession(['prefix' => 'managed_session']);
            expect($sessionStrategy)->toBeInstanceOf(SessionMemoryStrategy::class);

            $databaseStrategy = $manager->createDatabase(['default_ttl' => 1800]);
            expect($databaseStrategy)->toBeInstanceOf(DatabaseMemoryStrategy::class);

            $nullStrategy = $manager->createNull();
            expect($nullStrategy)->toBeInstanceOf(NullMemoryStrategy::class);

            // Create agents using managed strategies
            $sessionWrapper = ($this->createMemoryWrapper)($sessionStrategy);
            $databaseWrapper = ($this->createMemoryWrapper)($databaseStrategy);
            $nullWrapper = ($this->createMemoryWrapper)($nullStrategy);

            $sessionAgent = Agent::make()->setSystemPrompt('Session agent')->setMemory($sessionWrapper);
            $databaseAgent = Agent::make()->setSystemPrompt('Database agent')->setMemory($databaseWrapper);
            $nullAgent = Agent::make()->setSystemPrompt('Null agent')->setMemory($nullWrapper);

            // Verify all agents were created successfully
            expect($sessionAgent)->toHaveProperty('memory');
            expect($databaseAgent)->toHaveProperty('memory');
            expect($nullAgent)->toHaveProperty('memory');
        });
    });

    describe('Multi-strategy workflows', function () {
        test('agent workflow can switch between memory strategies', function () {
            // Create multiple strategies
            $sessionDefinition = new MemoryDefinition('session', ['prefix' => 'multi_session']);
            $databaseDefinition = new MemoryDefinition('database', []);

            $sessionFactory = new MemoryStrategyFactory($sessionDefinition);
            $databaseFactory = new MemoryStrategyFactory($databaseDefinition);

            $sessionStrategy = $sessionFactory->createInstance();
            $databaseStrategy = $databaseFactory->createInstance();

            // Set up session
            if ($sessionStrategy instanceof SessionMemoryStrategy) {
                $handler = new ArraySessionHandler(600);
                $session = new Store('multi_session', $handler);
                $session->start();
                $sessionStrategy->setSession($session);
            }

            $sessionWrapper = ($this->createMemoryWrapper)($sessionStrategy);
            $databaseWrapper = ($this->createMemoryWrapper)($databaseStrategy);

            // Create agent that can switch memory strategies
            $agent = Agent::make()->setSystemPrompt('Multi-strategy agent');

            // Phase 1: Use session memory for temporary data
            $agent->setMemory($sessionWrapper);
            expect($sessionWrapper->set('temp_data', 'session_temp'))->toBeTrue();
            expect($sessionWrapper->get('temp_data'))->toBe('session_temp');

            // Phase 2: Switch to database memory for persistent data
            $agent->setMemory($databaseWrapper);
            expect($databaseWrapper->set('persistent_data', 'database_persistent'))->toBeTrue();
            expect($databaseWrapper->get('persistent_data'))->toBe('database_persistent');

            // Verify data separation
            expect($sessionWrapper->get('persistent_data'))->toBeNull();
            expect($databaseWrapper->get('temp_data'))->toBeNull();

            // Verify database persistence
            expect(AgentMemory::where('key', 'persistent_data')->exists())->toBeTrue();

            // Clean up
            expect($sessionWrapper->flush())->toBeTrue();
            expect($databaseWrapper->flush())->toBeTrue();
        });

        test('coordinator with mixed memory strategies across agents', function () {
            // Create different strategies for different agents
            $sessionDefinition = new MemoryDefinition('session', ['prefix' => 'agent1_session']);
            $databaseDefinition = new MemoryDefinition('database', []);

            $sessionFactory = new MemoryStrategyFactory($sessionDefinition);
            $databaseFactory = new MemoryStrategyFactory($databaseDefinition);

            $sessionStrategy = $sessionFactory->createInstance();
            $databaseStrategy = $databaseFactory->createInstance();

            // Set up session
            if ($sessionStrategy instanceof SessionMemoryStrategy) {
                $handler = new ArraySessionHandler(600);
                $session = new Store('mixed_session', $handler);
                $session->start();
                $sessionStrategy->setSession($session);
            }

            $sessionWrapper = ($this->createMemoryWrapper)($sessionStrategy);
            $databaseWrapper = ($this->createMemoryWrapper)($databaseStrategy);

            // Create agents with different memory strategies
            $sessionAgent = Agent::make()->setSystemPrompt('Session-based agent')->setMemory($sessionWrapper);
            $databaseAgent = Agent::make()->setSystemPrompt('Database-based agent')->setMemory($databaseWrapper);

            // Create coordinator (could have its own shared memory)
            $sharedDefinition = new MemoryDefinition('database', ['default_ttl' => 7200]);
            $sharedFactory = new MemoryStrategyFactory($sharedDefinition);
            $sharedStrategy = $sharedFactory->createInstance();
            $sharedWrapper = ($this->createMemoryWrapper)($sharedStrategy);

            $coordinator = AgentCoordinator::make(
                'Mixed strategy coordination',
                [$sessionAgent, $databaseAgent],
                AgentStrategy::PARALLEL
            )->setMemory($sharedWrapper);

            // Pre-populate different memory types
            expect($sessionWrapper->set('session_specific', 'session_value'))->toBeTrue();
            expect($databaseWrapper->set('database_specific', 'database_value'))->toBeTrue();
            expect($sharedWrapper->set('coordinator_shared', 'shared_value'))->toBeTrue();

            // Verify isolation and sharing
            // Session memory is isolated (different session)
            expect($sessionWrapper->get('session_specific'))->toBe('session_value');
            expect($sessionWrapper->get('database_specific'))->toBeNull();
            expect($sessionWrapper->get('coordinator_shared'))->toBeNull();

            // Database strategies share the same database, so they can see each other's data
            expect($databaseWrapper->get('database_specific'))->toBe('database_value');
            expect($databaseWrapper->get('session_specific'))->toBeNull();
            // Database strategies share storage, so this will be accessible
            expect($databaseWrapper->get('coordinator_shared'))->toBe('shared_value');

            expect($sharedWrapper->get('coordinator_shared'))->toBe('shared_value');
            expect($sharedWrapper->get('session_specific'))->toBeNull();
            // Both database strategies use same storage
            expect($sharedWrapper->get('database_specific'))->toBe('database_value');

            // Clean up all strategies
            expect($sessionWrapper->flush())->toBeTrue();
            expect($databaseWrapper->flush())->toBeTrue();
            expect($sharedWrapper->flush())->toBeTrue();
        });
    });

    describe('Real-world workflow scenarios', function () {
        test('multi-step agent workflow with persistent checkpoints', function () {
            // Simulate a complex workflow that needs to persist state between steps
            $definition = new MemoryDefinition('database', ['default_ttl' => 3600]);
            $factory = new MemoryStrategyFactory($definition);
            $strategy = $factory->createInstance();
            $memoryWrapper = ($this->createMemoryWrapper)($strategy);

            $agent = Agent::make()->setSystemPrompt('Workflow processing agent');
            $agent->setMemory($memoryWrapper);

            // Step 1: Initialize workflow
            $workflowData = [
                'id' => 'workflow_001',
                'status' => 'initialized',
                'steps_completed' => [],
                'current_step' => 'step_1',
                'data' => ['input' => 'raw_data'],
            ];

            expect($memoryWrapper->set("workflow_{$workflowData['id']}", $workflowData))->toBeTrue();

            // Step 2: Process first step
            $retrieved = $memoryWrapper->get("workflow_{$workflowData['id']}");
            expect($retrieved['status'])->toBe('initialized');

            $retrieved['status'] = 'processing';
            $retrieved['current_step'] = 'step_2';
            $retrieved['steps_completed'][] = 'step_1';
            $retrieved['data']['step_1_result'] = 'processed_data';

            expect($memoryWrapper->set("workflow_{$workflowData['id']}", $retrieved))->toBeTrue();

            // Step 3: Complete workflow
            $final = $memoryWrapper->get("workflow_{$workflowData['id']}");
            $final['status'] = 'completed';
            $final['current_step'] = null;
            $final['steps_completed'][] = 'step_2';
            $final['data']['final_result'] = 'workflow_complete';

            expect($memoryWrapper->set("workflow_{$workflowData['id']}", $final))->toBeTrue();

            // Verify final state
            $completed = $memoryWrapper->get("workflow_{$workflowData['id']}");
            expect($completed['status'])->toBe('completed');
            expect($completed['steps_completed'])->toBe(['step_1', 'step_2']);
            expect($completed['data']['final_result'])->toBe('workflow_complete');

            // Verify database persistence (key includes workflow prefix)
            expect(AgentMemory::where('key', 'workflow_workflow_001')->exists())->toBeTrue();

            // Clean up
            expect($memoryWrapper->flush())->toBeTrue();
        });
    });
})->group('memory', 'agents', 'workflow', 'strategies', 'integration', 'unit');
