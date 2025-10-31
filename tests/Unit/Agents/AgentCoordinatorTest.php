<?php

use OpenAI\Testing\ClientFake;
use Illuminate\Support\Collection;
use Droath\NextusAi\Agents\Agent;
use Droath\NextusAi\Facades\NextusAi;
use Droath\NextusAi\Agents\AgentCoordinator;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Testing\Support\ResourceResponsesHelper;
use Droath\NextusAi\Agents\ValueObject\AgentCoordinatorResponse;

uses(RefreshDatabase::class);
uses(ResourceResponsesHelper::class);

test('parallel agent coordinator', closure: function () {
    /** @phpstan-ignore-next-line */
    $contentResponse = $this->createFakeJsonResponse([
        'title' => 'Apple Stock Price',
        'content' => 'The current stock price of Apple is $150.',
        'tags' => ['finance', 'stock'],
    ]);
    /** @phpstan-ignore-next-line */
    $metadataResponse = $this->createFakeJsonResponse([
        'assets' => [
            [
                'ticker' => 'AAPL',
                'sector' => 'stock',
                'open' => '175.00',
                'close' => '185.00',
                'high' => '195.00',
                'low' => '150.00',
            ],
        ],
    ]);

    $coordinatorResponse = $this->createFakeTextResponse('This is the LLM coordinator response');

    NextusAi::fake(resourceCallback: function () use ($contentResponse, $metadataResponse, $coordinatorResponse) {
        $client = (new ClientFake([$contentResponse, $metadataResponse, $coordinatorResponse]));

        return (new \Droath\NextusAi\Drivers\Openai($client))->responses();
    });

    $resource = NextusAi::responses(LlmProvider::OPENAI);

    $response = AgentCoordinator::make(
        'This is the website source content.',
        [
            Agent::make()->setSystemPrompt('Refine the website content.'),
            Agent::make()->setSystemPrompt('Extract the website metadata.'),
        ],
        strategy: AgentStrategy::PARALLEL
    )->run($resource);

    $response
        ->assertStrategyEqual(AgentStrategy::PARALLEL)
        ->assertAgentsUsing(function (Collection $agents) {
            expect($agents)
                ->toHaveCount(2)
                ->and($agents->get(0)->getInputs())->toHaveCount(2)
                ->and($agents->get(0)->getInputs()[0]->content)->toEqual(
                    'Refine the website content.'
                )
                ->and($agents->get(0)->getInputs()[1]->content)->toEqual(
                    'This is the website source content.'
                )
                ->and($agents->get(1)->getInputs())->toHaveCount(2)
                ->and($agents->get(1)->getInputs()[0]->content)->toEqual(
                    'Extract the website metadata.'
                )
                ->and($agents->get(1)->getInputs()[1]->content)->toEqual(
                    'This is the website source content.'
                );

            return true;
        });

    expect($response)
        ->toBeInstanceOf(AgentCoordinatorResponse::class)
        ->and($response->agentResponses())->toHaveCount(2)
        ->and($response->coordinatorResponse())->toBeInstanceOf(NextusAiResponseMessage::class);
});

test('sequential agent coordinator', closure: function () {
    /** @phpstan-ignore-next-line */
    $contentResponse = $this->createFakeTextResponse(
        'This is the LLM content response'
    );
    /** @phpstan-ignore-next-line */
    $metadataResponse = $this->createFakeTextResponse(
        'This is the LLM metadata response'
    );

    $coordinatorResponse = $this->createFakeTextResponse('This is the LLM coordinator response');

    NextusAi::fake(resourceCallback: function () use ($contentResponse, $metadataResponse, $coordinatorResponse) {
        $client = (new ClientFake([$contentResponse, $metadataResponse, $coordinatorResponse]));

        return (new \Droath\NextusAi\Drivers\Openai($client))->responses();
    });

    $resource = NextusAi::responses(LlmProvider::OPENAI);

    $response = AgentCoordinator::make(
        'This is the website source content.',
        [
            Agent::make()->setSystemPrompt('Create a website content.'),
            Agent::make()->setSystemPrompt('Enhance the website content for SEO.'),
        ],
        AgentStrategy::SEQUENTIAL
    )->run($resource);

    $response
        ->assertStrategyEqual(AgentStrategy::SEQUENTIAL)
        ->assertAgentsUsing(function (Collection $agents) {
            expect($agents)
                ->toHaveCount(2)
                ->and($agents->get(0)->getInputs())->toHaveCount(2)
                ->and($agents->get(0)->getInputs()[0]->content)->toEqual(
                    'Create a website content.'
                )
                ->and($agents->get(0)->getInputs()[1]->content)->toEqual(
                    'This is the website source content.'
                )
                ->and($agents->get(1)->getInputs())->toHaveCount(2)
                ->and($agents->get(1)->getInputs()[0]->content)->toEqual(
                    'Enhance the website content for SEO.'
                )
                ->and($agents->get(1)->getInputs()[1]->content)->toEqual(
                    'This is the LLM content response'
                );

            return true;
        });

    expect($response)
        ->toBeInstanceOf(AgentCoordinatorResponse::class)
        ->and($response->agentResponses())->toHaveCount(1)
        ->and($response->agentResponses()->get(0)->message)->toBe('This is the LLM metadata response')
        ->and($response->coordinatorResponse())->toBeInstanceOf(NextusAiResponseMessage::class);
});

test('router agent coordinator', closure: function () {
    NextusAi::fake();

    $response = AgentCoordinator::make(
        'Provide me with the latest news',
        [
            Agent::make()->setSystemPrompt('You are a sports agent that fetches the latest sports news.'),
            Agent::make()->setSystemPrompt('You are a weather agent that provides weather information.'),
        ],
        strategy: AgentStrategy::ROUTER
    )->run(NextusAi::responses(LlmProvider::OPENAI));

    $response
        ->assertStrategyEqual(AgentStrategy::ROUTER)
        ->assertAgentsUsing(function (Collection $agents) {
            expect($agents)->toHaveCount(2);

            return true;
        });

    expect($response)
        ->toBeInstanceOf(AgentCoordinatorResponse::class)
        ->and($response->agentResponses())->toBeEmpty()
        ->and($response->coordinatorResponse())->toBeInstanceOf(NextusAiResponseMessage::class);
});
