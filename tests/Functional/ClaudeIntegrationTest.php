<?php

declare(strict_types=1);

use Droath\NextusAi\NextusAiClient;
use Droath\NextusAi\Drivers\Claude;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Messages\UserMessage;
use Droath\NextusAi\Messages\SystemMessage;
use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Tools\ToolProperty;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

/**
 * Functional test for Claude integration with a real API key.
 *
 * This test requires ANTHROPIC_API_KEY environment variable to be set.
 *
 * Note: This test makes real API calls and should be run sparingly.
 */
describe('Claude Integration Functional Test', function () {
    test('can perform basic chat with real API', function () {
        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        expect($driver)->toBeInstanceOf(Claude::class);

        $chat = $driver->chat();

        $userMessage = UserMessage::make(
            'Hello! Please respond with exactly "Test successful" if you can see this message.'
        );
        $userMessage->setDriver($driver);

        $chat->withMessages([$userMessage]);

        $response = $chat();

        expect($response)->not->toBeNull()
            ->and($response->message)->toBeString()
            ->and($response->message)->toContain('Test successful');
    });

    test('can handle system messages with real API', function () {
        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        $systemMessage = SystemMessage::make('You are a helpful assistant. Always start your response with "SYSTEM:"');

        $userMessage = UserMessage::make('Say hello');
        $userMessage->setDriver($driver);

        $chat = $driver->chat();
        $chat->withMessages([$systemMessage, $userMessage]);

        $response = $chat();

        expect($response)->not->toBeNull()
            ->and($response->message)->toBeString()
            ->and($response->message)->toStartWith('SYSTEM:');
    });

    test('can handle tool calling with real API', function () {
        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        $weatherTool = Tool::make('get_weather')
            ->describe('Get current weather for a location')
            ->withProperties([
                ToolProperty::make('location', 'string')
                    ->describe('The city name')
                    ->required(),
                ToolProperty::make('unit', 'string')
                    ->describe('Temperature unit')
                    ->withEnums(['celsius', 'fahrenheit']),
            ])
            ->using(function (array $arguments) {
                $unit = $arguments['unit'];
                $location = $arguments['location'];

                return "The weather in $location is sunny with 22 $unit temperature.";
            });

        $chat = $driver->chat();
        $userMessage = UserMessage::make('What is the weather like in Paris? Use celsius.');
        $userMessage->setDriver($driver);

        $chat->withMessages([$userMessage])
            ->withTools([$weatherTool]);

        $response = $chat();

        expect($response)->not->toBeNull()
            ->and($response->message)->toContain('22')
            ->and($response->message)->toContain('Paris')
            ->and($response->message)->toContain('sunny');
    });

    test('can handle longer conversations', function () {
        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        $chat = $driver->chat();

        // First message
        $userMessage1 = UserMessage::make('My name is Test User. Remember this.');
        $userMessage1->setDriver($driver);
        $chat->withMessages([$userMessage1]);

        $response1 = $chat();
        expect($response1)->not->toBeNull();

        // Second message that references the first
        $userMessage2 = UserMessage::make('What is my name?');
        $userMessage2->setDriver($driver);

        // Add both previous messages to maintain conversation history
        $chat->withMessages([
            $userMessage1,
            // Simulate assistant response (in real conversation this would be stored)
            $userMessage2,
        ]);

        $response2 = $chat();

        expect($response2)->not->toBeNull()
            ->and($response2->message)->toBeString()
            ->and($response2->message)->toContain('Test User');
    });

    test('can handle non-streaming responses with tools', function () {
        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        $toolCalled = false;

        $calculatorTool = Tool::make('calculate')
            ->describe('Perform basic arithmetic calculations')
            ->withProperties([
                ToolProperty::make('expression', 'string')
                    ->describe('Mathematical expression to calculate (e.g., "2+2")')
                    ->required(),
            ])
            ->using(function (array $args) use (&$toolCalled) {
                $toolCalled = true;
                $expression = $args['expression'];

                // Simple calculator for basic expressions
                if (preg_match('/^(\d+)\s*\+\s*(\d+)$/', $expression, $matches)) {
                    $result = (int) $matches[1] + (int) $matches[2];

                    return "The result of {$expression} is {$result}";
                }

                return "Calculated result for: {$expression}";
            });

        $chat = $driver->chat();
        $userMessage = UserMessage::make('Please calculate 5+3 for me using the calculator tool.');
        $userMessage->setDriver($driver);

        $chat->withMessages([$userMessage])
            ->withTools([$calculatorTool]);

        $response = $chat();

        expect($response)->not->toBeNull()
            ->and($toolCalled)->toBeTrue()
            ->and($response->message)->toContain('8'); // Should contain the result
    });

    test('can handle streaming responses without tools', function () {
        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        $chat = $driver->chat();
        $userMessage = UserMessage::make('Say "Streaming test successful" and nothing else.');
        $userMessage->setDriver($driver);

        $streamOutput = '';
        $streamFinished = false;

        $chat->withMessages([$userMessage])
            ->usingStream(
                function (string $chunk, bool $initialized) use (&$streamOutput) {
                    $streamOutput .= $chunk;
                },
                function (NextusAiResponseMessage $response) use (&$streamFinished) {
                    $streamFinished = true;
                }
            );

        $response = $chat();

        expect($response)->not->toBeNull()
            ->and($response->message)->toBeString()
            ->and($response->message)->toContain('Streaming test successful')
            ->and($streamFinished)->toBeTrue()
            ->and($streamOutput)->toContain('Streaming test successful');
    });

    test('can handle streaming responses with tools', function () {
        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        $weatherTool = Tool::make('get_weather')
            ->describe('Get current weather for a location')
            ->withProperties([
                ToolProperty::make('location', 'string')
                    ->describe('The city name')
                    ->required(),
            ])
            ->using(function (array $arguments) {
                return "The weather in {$arguments['location']} is sunny with 22°C.";
            });

        $chat = $driver->chat();
        $userMessage = UserMessage::make('What is the weather like in London? Use the weather tool.');
        $userMessage->setDriver($driver);

        $streamOutput = '';
        $streamFinished = false;

        $chat->withMessages([$userMessage])
            ->withTools([$weatherTool])
            ->usingStream(
                function (string $chunk, bool $initialized) use (&$streamOutput) {
                    $streamOutput .= $chunk;
                },
                function (NextusAiResponseMessage $response) use (&$streamFinished) {
                    $streamFinished = true;
                }
            );

        $response = $chat();

        expect($response)->not->toBeNull()
            ->and($streamFinished)->toBeTrue()
            ->and($response->message)->toContain('22')
            ->and($response->message)->toContain('London')
            ->and($response->message)->toContain('sunny');
    });

    test('validates configuration correctly', function () {
        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        $errors = $driver->validateConfiguration();
        expect($errors)->toBeEmpty();

        $modelErrors = $driver->validateModel('claude-3-5-sonnet-20241022');
        expect($modelErrors)->toBeEmpty();

        $invalidModelErrors = $driver->validateModel('invalid-model');
        expect($invalidModelErrors)->not->toBeEmpty();
    });
})->skip(
    (! isset($_ENV['ANTHROPIC_API_KEY'], $_ENV['ANTHROPIC_FUNCTIONAL_TEST'])
        || $_ENV['ANTHROPIC_FUNCTIONAL_TEST'] !== 'true'),
    'Requires ANTHROPIC_FUNCTIONAL_TEST=true and ANTHROPIC_API_KEY environment variables'
);
