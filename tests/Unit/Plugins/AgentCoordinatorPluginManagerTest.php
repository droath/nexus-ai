<?php

declare(strict_types=1);

use OpenAI\Testing\ClientFake;
use Illuminate\Support\Collection;
use Droath\NextusAi\Facades\NextusAi;
use Droath\NextusAi\Agents\Enums\AgentStrategy;
use Droath\NextusAi\Plugins\AgentPluginManager;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Plugins\AgentCoordinatorPluginManager;
use Droath\NextusAi\Resources\Contracts\ResourceInterface;
use Droath\NextusAi\Testing\Support\ResourceResponsesHelper;
use Droath\NextusAi\Agents\ValueObject\AgentCoordinatorResponse;

uses(ResourceResponsesHelper::class);

beforeEach(function () {
    $testNamespace = 'Droath\NextusAi\Tests\Fixtures\Plugins';
    $this->swap(
        AgentPluginManager::class,
        new AgentPluginManager([$testNamespace])
    );
    $this->manager = new AgentCoordinatorPluginManager([$testNamespace]);
});

test('agent coordinator plugins using a parallel strategy', function () {
    $contentResponse = $this->createFakeJsonResponse([
        'title' => 'Apple Stock Price',
        'content' => 'The current stock price of Apple is $150.',
        'tags' => ['finance', 'stock'],
    ]);

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

        return (new Droath\NextusAi\Drivers\Openai($client))->structured();
    });

    /** @var Droath\NextusAi\Plugins\AgentCoordinator\AgentCoordinatorPlugin $coordinator */
    $coordinator = $this->manager->createInstance('content_agent_coordinator');

    $response = $coordinator->respond('This is the website source content.');

    $response
        ->assertStrategyEqual(AgentStrategy::PARALLEL)
        ->assertAgentsUsing(function (Collection $agents) {
            expect($agents)
                ->toHaveCount(2)
                ->and($agents->get(0)->name())->toEqual('content_agent')
                ->and($agents->get(0)->getInputs())->toHaveCount(3)
                ->and($agents->get(0)->getInputs()[0]->content)->toEqual(
                    'You are a content agent for generating content'
                )
                ->and($agents->get(0)->getInputs()[2]->content)->toEqual(
                    'This is the website source content.'
                )
                ->and($agents->get(1)->name())->toEqual('metadata_agent');

            return true;
        });

    expect($response)
        ->toBeInstanceOf(AgentCoordinatorResponse::class)
        ->and($response->agentResponses())->toHaveCount(2)
        ->and($response->coordinatorResponse())->toBeInstanceOf(NextusAiResponseMessage::class);
});

test('agent coordinator plugin using the sequence strategy', function () {
    NextusAi::fake();

    /** @var Droath\NextusAi\Plugins\AgentCoordinator\AgentCoordinatorPlugin $coordinator */
    $coordinator = $this->manager->createInstance('research_agent_coordinator');

    $response = $coordinator->respond('What is the meaning of life?');

    $response
        ->assertStrategyEqual(AgentStrategy::SEQUENTIAL)
        ->assertAgentsUsing(function (Collection $agents) {
            expect($agents)
                ->toHaveCount(2)
                ->and($agents->get(0)->name())->toEqual('research_agent')
                ->and($agents->get(0)->getInputs()[0]->content)->toEqual(
                    'You are a helpful research agent, you task to help users fetch the latest academic articles.'
                )
                ->and($agents->get(0)->getInputs()[1]->content)
                ->toEqual('What is the meaning of life?')
                ->and($agents->get(1)->name())->toEqual('analysis_agent')
                ->and($agents->get(1)->getInputs()[0]->content)->toEqual(
                    'You are a helpful agent that analyzes the data provided by the research team.'
                )
                ->and($agents->get(1)->getInputs()[1]->content)
                ->toEqual('{"research":"This is a fake response."}');

            return true;
        });

    expect($response)
        ->toBeInstanceOf(AgentCoordinatorResponse::class)
        ->and($response->agentResponses())->toHaveCount(1)
        ->and($response->coordinatorResponse())->toBeInstanceOf(NextusAiResponseMessage::class);

});

test('agent coordinator plugin using router strategy', closure: function () {
    NextusAi::fake();

    /** @var Droath\NextusAi\Plugins\AgentCoordinator\AgentCoordinatorPlugin $coordinator */
    $coordinator = $this->manager->createInstance('reporter_agent_coordinator');

    $response = $coordinator->respond('Provide me with the latest news');

    $response
        ->assertStrategyEqual(AgentStrategy::ROUTER)
        ->assertAgentsUsing(function (Collection $agents) {
            expect($agents)
                ->toHaveCount(2)
                ->and($agents->get(0)->name())->toEqual('sport_agent')
                ->and($agents->get(1)->name())->toEqual('weather_agent');

            return true;
        })->assertResourceUsing(function (ResourceInterface $resource) {
            $resource = invade($resource);

            $sportAgentTool = invade($resource->tools->get(0));
            $sportAgentToolProperty = invade($sportAgentTool->properties[0]);

            expect($sportAgentTool)
                ->name->toEqual('sport_agent')
                ->description->toEqual('A sports agent that fetches the latest sports news.')
                ->and($sportAgentToolProperty)
                ->name->toEqual('question')
                ->type->toEqual('string')
                ->description->toEqual('The question to ask the sport_agent agent.');

            return true;
        });

    expect($response)
        ->toBeInstanceOf(AgentCoordinatorResponse::class)
        ->and($response->agentResponses())->toBeEmpty()
        ->and($response->coordinatorResponse())->toBeInstanceOf(NextusAiResponseMessage::class);
});
