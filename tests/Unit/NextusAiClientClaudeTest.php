<?php

declare(strict_types=1);

use Droath\NextusAi\NextusAiClient;
use Droath\NextusAi\Drivers\Claude;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Anthropic\Contracts\ClientContract;

describe('NextusAiClient Claude Integration', function () {
    beforeEach(function () {
        // Mock the config
        config([
            'nextus-ai.claude.api_key' => 'test-api-key',
            'nextus-ai.claude.base_url' => 'https://api.anthropic.com',
        ]);
    });

    test('can create claude driver', function () {
        $hubClient = new NextusAiClient(app());

        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        expect($driver)->toBeInstanceOf(Claude::class);
    });

    test('claude driver has anthropic client', function () {
        $hubClient = new NextusAiClient(app());

        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        expect($driver->client())->toBeInstanceOf(ClientContract::class);
    });

    test('claude driver uses configured api key', function () {
        config(['nextus-ai.claude.api_key' => 'custom-key']);

        $hubClient = new NextusAiClient(app());
        $driver = $hubClient->driver(LlmProvider::CLAUDE->value);

        expect($driver)->toBeInstanceOf(Claude::class)
            ->and($driver->client())->toBeInstanceOf(ClientContract::class);
    });

    test('claude is available as provider option', function () {
        $providers = LlmProvider::cases();

        $claudeProvider = collect($providers)->first(fn ($provider) => $provider === LlmProvider::CLAUDE);

        expect($claudeProvider)->not->toBeNull()
            ->and($claudeProvider->value)->toBe('claude');
    });
});
