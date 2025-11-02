<?php

declare(strict_types=1);

use Illuminate\Session\Store;
use Illuminate\Session\ArraySessionHandler;
use Droath\NextusAi\Memory\Strategies\SessionMemoryStrategy;
use Droath\NextusAi\Memory\Contracts\MemoryStrategyInterface;

describe('SessionMemoryStrategy', function () {
    beforeEach(function () {
        // Create a test session store with array handler
        $handler = new ArraySessionHandler(600);
        $session = new Store('test_session', $handler);
        $session->start();

        $this->strategy = (new SessionMemoryStrategy([
            'prefix' => 'agent_memory',
        ]))->setSession($session);

        $this->session = $session;
    });

    describe('instantiation and interface compliance', function () {
        test('can be instantiated with default configuration', function () {
            $strategy = new SessionMemoryStrategy([]);

            expect($strategy)->toBeInstanceOf(SessionMemoryStrategy::class)
                ->and($strategy)->toBeInstanceOf(MemoryStrategyInterface::class);
        });

        test('can be instantiated with custom configuration', function () {
            $config = ['prefix' => 'custom_memory'];
            $strategy = new SessionMemoryStrategy($config);

            expect($strategy)->toBeInstanceOf(SessionMemoryStrategy::class);
        });

        test('implements required interfaces', function () {
            expect($this->strategy)->toBeInstanceOf(MemoryStrategyInterface::class);
        });
    });

    describe('set method', function () {
        test('stores value in session with prefixed key', function () {
            $key = 'test_key';
            $value = 'test_value';

            $result = $this->strategy->set($key, $value);

            expect($result)->toBeTrue();
            expect($this->session->get('agent_memory.test_key'))->toBe($value);
        });

        test('ignores TTL parameter for session storage', function () {
            $key = 'test_key';
            $value = 'test_value';
            $ttl = 3600; // Should be ignored

            $result = $this->strategy->set($key, $value, $ttl);

            expect($result)->toBeTrue();
            expect($this->session->get('agent_memory.test_key'))->toBe($value);
        });

        test('stores complex data structures', function () {
            $key = 'complex_data';
            $value = [
                'array' => [1, 2, 3],
                'object' => (object) ['prop' => 'value'],
                'nested' => ['deep' => ['data' => true]],
            ];

            $result = $this->strategy->set($key, $value);

            expect($result)->toBeTrue();
            expect($this->session->get('agent_memory.complex_data'))->toEqual($value);
        });

        test('handles null values', function () {
            $key = 'null_key';
            $value = null;

            $result = $this->strategy->set($key, $value);

            expect($result)->toBeTrue();
            expect($this->session->get('agent_memory.null_key'))->toBeNull();
        });

        test('handles empty string keys', function () {
            $key = '';
            $value = 'test_value';

            $result = $this->strategy->set($key, $value);

            expect($result)->toBeTrue();
            expect($this->session->get('agent_memory.'))->toBe($value);
        });
    });

    describe('get method', function () {
        test('retrieves value from session with prefixed key', function () {
            $key = 'test_key';
            $value = 'test_value';
            $default = 'default_value';

            $this->session->put('agent_memory.test_key', $value);

            $result = $this->strategy->get($key, $default);

            expect($result)->toBe($value);
        });

        test('returns default value when key not found', function () {
            $key = 'missing_key';
            $default = 'default_value';

            $result = $this->strategy->get($key, $default);

            expect($result)->toBe($default);
        });

        test('handles null default value', function () {
            $key = 'test_key';
            $default = null;

            $result = $this->strategy->get($key, $default);

            expect($result)->toBeNull();
        });

        test('retrieves complex data structures', function () {
            $key = 'complex_data';
            $value = ['array' => [1, 2, 3], 'nested' => ['deep' => true]];

            $this->session->put('agent_memory.complex_data', $value);

            $result = $this->strategy->get($key);

            expect($result)->toEqual($value);
        });
    });

    describe('has method', function () {
        test('returns true when key exists in session', function () {
            $key = 'existing_key';

            $this->session->put('agent_memory.existing_key', 'value');

            $result = $this->strategy->has($key);

            expect($result)->toBeTrue();
        });

        test('returns false when key does not exist in session', function () {
            $key = 'missing_key';

            $result = $this->strategy->has($key);

            expect($result)->toBeFalse();
        });

        test('handles empty string key', function () {
            $key = '';

            $result = $this->strategy->has($key);

            expect($result)->toBeFalse();
        });
    });

    describe('forget method', function () {
        test('removes key from session', function () {
            $key = 'key_to_remove';

            $this->session->put('agent_memory.key_to_remove', 'value');

            $result = $this->strategy->forget($key);

            expect($result)->toBeTrue();
            expect($this->session->has('agent_memory.key_to_remove'))->toBeFalse();
        });

        test('handles non-existent keys gracefully', function () {
            $key = 'non_existent_key';

            $result = $this->strategy->forget($key);

            expect($result)->toBeTrue();
        });

        test('handles empty string key', function () {
            $key = '';

            $this->session->put('agent_memory.', 'value');

            $result = $this->strategy->forget($key);

            expect($result)->toBeTrue();
            expect($this->session->has('agent_memory.'))->toBeFalse();
        });
    });

    describe('flush method', function () {
        test('removes all memory keys from session', function () {
            // Set some values through the strategy (which should work)
            $this->strategy->set('key1', 'value1');
            $this->strategy->set('key2', 'value2');
            $this->strategy->set('key3', 'value3');
            $this->session->put('other_session_key', 'other_value');

            $result = $this->strategy->flush();

            expect($result)->toBeTrue();
            expect($this->strategy->has('key1'))->toBeFalse();
            expect($this->strategy->has('key2'))->toBeFalse();
            expect($this->strategy->has('key3'))->toBeFalse();
            expect($this->session->get('other_session_key'))->toBe('other_value');
        });

        test('handles empty session gracefully', function () {
            $result = $this->strategy->flush();

            expect($result)->toBeTrue();
        });

        test('only removes keys with correct prefix', function () {
            // Use strategy to set one value, and direct session for others
            $this->strategy->set('key1', 'value1');
            $this->session->put('other_prefix.key2', 'value2');
            $this->session->put('unrelated_key', 'value');

            $result = $this->strategy->flush();

            expect($result)->toBeTrue();
            expect($this->strategy->has('key1'))->toBeFalse();
            expect($this->session->get('other_prefix.key2'))->toBe('value2');
            expect($this->session->get('unrelated_key'))->toBe('value');
        });
    });

    describe('strategy-specific methods', function () {
        test('cleanupExpired returns true as session handles expiration', function () {
            // Session strategy doesn't need manual cleanup, returns true to indicate success
            expect($this->strategy->cleanupExpired())->toBeTrue();
        });
    });

    describe('session prefix handling', function () {
        test('uses custom prefix when provided', function () {
            $handler = new ArraySessionHandler(600);
            $session = new Store('test_session', $handler);
            $session->start();

            $customPrefix = 'custom_agent_mem';
            $strategy = (new SessionMemoryStrategy([
                'prefix' => $customPrefix,
            ]))->setSession($session);

            $key = 'test_key';
            $value = 'test_value';

            $result = $strategy->set($key, $value);

            expect($result)->toBeTrue();
            expect($session->get('custom_agent_mem.test_key'))->toBe($value);
        });

        test('uses default prefix when none provided', function () {
            $handler = new ArraySessionHandler(600);
            $session = new Store('test_session', $handler);
            $session->start();

            $strategy = (new SessionMemoryStrategy([]))->setSession($session);

            $key = 'test_key';
            $value = 'test_value';

            $result = $strategy->set($key, $value);

            expect($result)->toBeTrue();
            expect($session->get('agent_memory.test_key'))->toBe($value);
        });

        test('handles empty prefix gracefully', function () {
            $handler = new ArraySessionHandler(600);
            $session = new Store('test_session', $handler);
            $session->start();

            $strategy = (new SessionMemoryStrategy([
                'prefix' => '',
            ]))->setSession($session);

            $key = 'test_key';
            $value = 'test_value';

            $result = $strategy->set($key, $value);

            expect($result)->toBeTrue();
            expect($session->get('.test_key'))->toBe($value);
        });
    });

    describe('integration scenarios', function () {
        test('can store and retrieve the same value', function () {
            $key = 'integration_key';
            $value = 'integration_value';

            $setResult = $this->strategy->set($key, $value);
            $getResult = $this->strategy->get($key);

            expect($setResult)->toBeTrue()
                ->and($getResult)->toBe($value);
        });

        test('reflects has status correctly after set and forget', function () {
            $key = 'status_key';
            $value = 'status_value';

            // Set value and check it exists
            $setResult = $this->strategy->set($key, $value);
            $hasResult = $this->strategy->has($key);

            expect($setResult)->toBeTrue()
                ->and($hasResult)->toBeTrue();

            // Forget value and check it no longer exists
            $forgetResult = $this->strategy->forget($key);
            $hasAfterForgetResult = $this->strategy->has($key);

            expect($forgetResult)->toBeTrue()
                ->and($hasAfterForgetResult)->toBeFalse();
        });
    });

    describe('setSession method', function () {
        test('can override session with setSession method', function () {
            $handler = new ArraySessionHandler(600);
            $newSession = new Store('new_test_session', $handler);
            $newSession->start();

            $strategy = new SessionMemoryStrategy(['prefix' => 'test_prefix']);
            $strategy->setSession($newSession);

            $key = 'override_test';
            $value = 'override_value';

            $result = $strategy->set($key, $value);

            expect($result)->toBeTrue();
            expect($newSession->get('test_prefix.override_test'))->toBe($value);
        });
    });
})->group('memory', 'session', 'unit');
