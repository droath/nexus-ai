<?php

declare(strict_types=1);

use OpenAI\Testing\ClientFake;
use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Agents\Agent;
use Droath\NextusAi\Facades\NextusAi;
use Droath\NextusAi\Tools\ToolProperty;
use Droath\NextusAi\Messages\UserMessage;
use Droath\NextusAi\Messages\SystemMessage;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Testing\Support\ResourceResponsesHelper;
use Droath\NextusAi\Resources\Contracts\StructuredResourceInterface;

uses(RefreshDatabase::class);
uses(ResourceResponsesHelper::class);

test('agent with tool', function () {
    /** @phpstan-ignore-next-line */
    $resourceResponse = $this->createFakeTextResponse("It's 89 degrees in Denver, CO.");

    NextusAi::fake(resourceCallback: function () use ($resourceResponse) {
        $client = (new ClientFake([$resourceResponse]));

        return (new Droath\NextusAi\Drivers\Openai($client))->structured();
    });
    $tool = Tool::make('get_weather')
        ->describe('Get the current weather in a given location')
        ->using(function () {
            return "It's 89 degrees";
        })
        ->withProperties([
            ToolProperty::make('location', 'string')
                ->describe('The city and state, e.g. San Francisco, CA')
                ->required(),
            ToolProperty::make('unit', 'string')
                ->describe('The unit of measurement to return. Can be "imperial" or "metric".')
                ->withEnums(['celsius', 'fahrenheit']),
        ]);

    $resource = NextusAi::structured(LlmProvider::OPENAI);

    $agentResponse = Agent::make()
        ->setSystemPrompt('You are a weather bot')
        ->addInput('What is the weather in Denver, CO?')
        ->addTool($tool)
        ->run($resource);

    NextusAi::assertResource(function (StructuredResourceInterface $resource) {
        $resource = invade($resource);

        /** @phpstan-ignore-next-line */
        $messages = $resource->messages;
        /** @phpstan-ignore-next-line */
        $tools = $resource->resolveTools();

        expect($messages)->toHaveCount(2)
            ->and($messages[0]->content)->toBe('You are a weather bot')
            ->and($messages[0])->toBeInstanceOf(SystemMessage::class)
            ->and($messages[1]->content)->toBe('What is the weather in Denver, CO?')
            ->and($messages[1])->toBeInstanceOf(UserMessage::class)
            ->and($tools[0])->name->toEqual('get_weather')
            ->and($tools[0]['parameters']['properties'])->toHaveKeys(['location', 'unit'])
            ->and($tools[0]['parameters']['required'])->toHaveCount(1)->toContain('location');
    });

    expect($agentResponse)
        ->toBeInstanceOf(NextusAiResponseMessage::class)
        ->and($agentResponse->message)->toEqual("It's 89 degrees in Denver, CO.");
});
