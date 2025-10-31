<?php

declare(strict_types=1);

use Anthropic\Contracts\ClientContract;
use Anthropic\Testing\ClientFake;
use Droath\NextusAi\Drivers\Claude;
use Droath\NextusAi\Drivers\NextusAiDriver;
use Droath\NextusAi\Drivers\Contracts\HasChatInterface;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Tools\ToolProperty;

describe('Claude Driver', function () {
    test('can be instantiated', function () {
        $client = new ClientFake();
        $driver = new Claude($client);

        expect($driver)->toBeInstanceOf(Claude::class)
            ->and($driver)->toBeInstanceOf(NextusAiDriver::class);
    });

    test('implements required interfaces', function () {
        $client = new ClientFake();
        $driver = new Claude($client);

        expect($driver)->toBeInstanceOf(HasChatInterface::class);
    });

    test('has correct default model', function () {
        expect(Claude::DEFAULT_MODEL)->toBe('claude-3-haiku-20240307');
    });

    it('accepts anthropic client in constructor', function () {
        $client = new ClientFake();
        $driver = new Claude($client);

        expect(invade($driver)->client)->toBeInstanceOf(ClientContract::class);
    });

    describe('Authentication and API Connection', function () {
        test('stores client instance correctly', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            expect($driver->client())
                ->toBeInstanceOf(ClientContract::class)
                ->toBe($client);
        });

        test('client instance is accessible via getClient method', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            expect($driver->client())->toBe($client)
                ->and(method_exists($driver, 'client'))->toBeTrue();
        });

        test('maintains client reference through constructor injection', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            expect(invade($driver)->client)->toBe($client);
        });

        test('client is properly protected and accessible to subclasses', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $reflection = new ReflectionClass($driver);
            $clientProperty = $reflection->getProperty('client');

            expect($clientProperty->isProtected())->toBeTrue()
                ->and($clientProperty->isPrivate())->toBeFalse()
                ->and($clientProperty->isPublic())->toBeFalse();
        });

        test('accepts different client configurations', function () {
            // Test with different client instances
            $client1 = new ClientFake();
            $client2 = new ClientFake();

            $driver1 = new Claude($client1);
            $driver2 = new Claude($client2);

            expect($driver1->client())->toBe($client1)
                ->and($driver2->client())->toBe($client2)
                ->and($driver1->client())->not->toBe($driver2->client());
        });
    });

    describe('Chat Functionality', function () {
        test('provides chat resource', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $chatResource = $driver->chat();

            expect($chatResource)->toBeInstanceOf(ChatResourceInterface::class);
        });

        test('chat resource uses same client instance', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $chatResource = $driver->chat();

            expect(invade($chatResource)->client)->toBe($client);
        });
    });

    describe('Tool Transformation', function () {
        test('transforms tool correctly', function () {
            $tool = Tool::make('test_tool')
                ->describe('A test tool')
                ->withProperties([
                    ToolProperty::make('param1', 'string')
                        ->describe('First parameter')
                        ->required(),
                ]);

            $transformed = Claude::transformTool($tool);

            expect($transformed)->toBe([
                'name' => 'test_tool',
                'description' => 'A test tool',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'param1' => [
                            'type' => 'string',
                            'description' => 'First parameter',
                        ],
                    ],
                    'required' => ['param1'],
                ],
            ]);
        });

        test('handles tool with no parameters', function () {
            $tool = Tool::make('simple_tool')
                ->describe('A simple tool');

            $transformed = Claude::transformTool($tool);

            expect($transformed)->toBe([
                'name' => 'simple_tool',
                'description' => 'A simple tool',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
            ]);
        });

        test('handles tool with optional parameters', function () {
            $tool = Tool::make('optional_tool')
                ->describe('A tool with optional parameters')
                ->withProperties([
                    ToolProperty::make('param1', 'string')
                        ->describe('Optional parameter'),
                ]);

            $transformed = Claude::transformTool($tool);

            expect($transformed)->toBe([
                'name' => 'optional_tool',
                'description' => 'A tool with optional parameters',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'param1' => [
                            'type' => 'string',
                            'description' => 'Optional parameter',
                        ],
                    ],
                    'required' => [],
                ],
            ]);
        });
    });

    describe('Message Transformation', function () {
        test('transforms user message correctly', function () {
            $content = 'Hello, Claude!';
            $transformed = Claude::transformUserMessage($content);

            expect($transformed)->toBe($content);
        });

        test('returns string as-is for user messages', function () {
            $content = 'This is a test message with special characters: !@#$%^&*()';
            $transformed = Claude::transformUserMessage($content);

            expect($transformed)->toBe($content)
                ->and($transformed)->toBeString();
        });

        test('handles empty user message', function () {
            $content = '';
            $transformed = Claude::transformUserMessage($content);

            expect($transformed)->toBe('')
                ->and($transformed)->toBeString();
        });
    });

    describe('Configuration Validation', function () {
        test('validates configuration with valid API key', function () {
            config(['nextus-ai.claude.api_key' => 'sk-ant-api03-valid_key_here']);

            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateConfiguration();

            expect($errors)->toBeEmpty();
        });

        test('detects missing API key', function () {
            config(['nextus-ai.claude.api_key' => null]);

            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateConfiguration();

            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toContain('Claude API key is not configured');
        });

        test('detects empty API key', function () {
            config(['nextus-ai.claude.api_key' => '']);

            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateConfiguration();

            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toContain('Claude API key is not configured');
        });

        test('detects invalid API key format', function () {
            config(['nextus-ai.claude.api_key' => 'invalid-key-format']);

            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateConfiguration();

            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toContain('Claude API key format is invalid');
        });

        test('detects short API key', function () {
            config(['nextus-ai.claude.api_key' => 'sk-ant-123']);

            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateConfiguration();

            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toContain('Claude API key format is invalid');
        });
    });

    describe('Model Validation', function () {
        test('validates correct model format', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateModel('claude-3-sonnet-20240229');

            expect($errors)->toBeEmpty();
        });

        test('validates current default model', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateModel(Claude::DEFAULT_MODEL);

            expect($errors)->toBeEmpty();
        });

        test('detects empty model name', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateModel('');

            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toContain('Model name cannot be empty');
        });

        test('detects invalid model format - no claude prefix', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateModel('gpt-4-turbo');

            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toContain('has an invalid format');
        });

        test('detects invalid model format - too short', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $errors = $driver->validateModel('claude-3');

            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toContain('has an invalid format');
        });

        test('detects invalid model format - too long', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $tooLongModel = 'claude-'.str_repeat('a', 50);
            $errors = $driver->validateModel($tooLongModel);

            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toContain('has an invalid format');
        });

        test('accepts various valid claude model formats', function () {
            $client = new ClientFake();
            $driver = new Claude($client);

            $validModels = [
                'claude-3-opus-20240229',
                'claude-3-haiku-20240307',
                'claude-3-5-sonnet-20240620',
                'claude-4-sonnet-preview',
            ];

            foreach ($validModels as $model) {
                $errors = $driver->validateModel($model);
                expect($errors)->toBeEmpty("Model {$model} should be valid");
            }
        });
    });
});
