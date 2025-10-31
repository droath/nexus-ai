<?php

declare(strict_types=1);

use Anthropic\Testing\ClientFake;
use Anthropic\Responses\Messages\CreateResponse;
use Droath\NextusAi\Drivers\Claude;
use Droath\NextusAi\Resources\ClaudeChatResource;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\HasDriverInterface;
use Droath\NextusAi\Resources\Contracts\HasMessagesInterface;
use Droath\NextusAi\Resources\Contracts\HasResponseFormatInterface;
use Droath\NextusAi\Resources\Contracts\HasToolsInterface;
use Droath\NextusAi\Drivers\Contracts\HasStreamingInterface;
use Droath\NextusAi\Messages\UserMessage;
use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Tools\ToolProperty;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

describe('ClaudeChatResource', function () {
    test('can be instantiated', function () {
        $client = new ClientFake();
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        expect($resource)->toBeInstanceOf(ClaudeChatResource::class)
            ->and($resource)->toBeInstanceOf(ChatResourceInterface::class);
    });

    test('implements required interfaces', function () {
        $client = new ClientFake();
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        expect($resource)->toBeInstanceOf(HasMessagesInterface::class)
            ->and($resource)->toBeInstanceOf(HasResponseFormatInterface::class)
            ->and($resource)->toBeInstanceOf(HasDriverInterface::class)
            ->and($resource)->toBeInstanceOf(HasStreamingInterface::class)
            ->and($resource)->toBeInstanceOf(HasToolsInterface::class);
    });

    test('has correct default model', function () {
        $client = new ClientFake();
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        expect(invade($resource)->model)->toBe(Claude::DEFAULT_MODEL);
    });

    test('can set model', function () {
        $client = new ClientFake();
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        $newResource = $resource->withModel('claude-3-haiku-20240307');

        expect(invade($newResource)->model)->toBe('claude-3-haiku-20240307')
            ->and($newResource)->toBe($resource);
    });

    test('stores client and driver instances correctly', function () {
        $client = new ClientFake();
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        expect(invade($resource)->client)->toBe($client)
            ->and($resource->driver())->toBe($driver);
    });

    test('can handle tools', function () {
        $client = new ClientFake();
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        $tool = Tool::make('get_weather')
            ->describe('Get weather information for a location')
            ->withProperties([
                ToolProperty::make('location', 'string')
                    ->describe('The location to get weather for')
                    ->required(),
            ]);

        $resource->withTools([$tool]);

        expect(invade($resource)->tools)->toHaveCount(1);
    });

    test('transforms tools correctly', function () {
        $tool = Tool::make('test_tool')
            ->describe('A test tool')
            ->withProperties([
                ToolProperty::make('param', 'string')
                    ->describe('A parameter')
                    ->required(),
            ]);

        $transformed = Claude::transformTool($tool);

        expect($transformed)->toHaveKey('name')
            ->and($transformed['name'])->toBe('test_tool')
            ->and($transformed)->toHaveKey('description')
            ->and($transformed['description'])->toBe('A test tool')
            ->and($transformed)->toHaveKey('input_schema')
            ->and($transformed['input_schema'])->toBeArray();
    });

    test('can configure streaming', function () {
        $client = new ClientFake();
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        $streamProcess = fn (string $chunk, bool $initialized) => null;
        $streamFinished = fn (NextusAiResponseMessage $response) => null;

        $newResource = $resource->usingStream($streamProcess, $streamFinished);

        expect($newResource)->toBe($resource)
            ->and(invade($resource)->stream)->toBeTrue();
    });

    test('processes successful response correctly', function () {
        $client = new ClientFake([
            CreateResponse::fake([
                'id' => 'msg_123',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello, how can I help you?',
                    ],
                ],
            ]),
        ]);
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        $userMessage = UserMessage::make('Hello');
        $userMessage->setDriver($driver);
        $resource->withMessages([$userMessage]);

        $result = $resource();

        expect($result)->toBeInstanceOf(NextusAiResponseMessage::class)
            ->and($result->message)->toBe('Hello, how can I help you?');
    });

    test('handles empty messages gracefully', function () {
        // This test verifies that empty messages are handled without making API calls
        $client = new ClientFake();
        $driver = new Claude($client);
        $resource = new ClaudeChatResource($client, $driver);

        $resource->withMessages([]);

        // Should return null without making API call (no fake responses needed)
        $result = $resource();

        expect($result)->toBeNull();
    });
});
