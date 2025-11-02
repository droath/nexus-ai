<?php

declare(strict_types=1);

namespace Droath\NextusAi;

use Anthropic;
use GuzzleHttp\Client;
use Illuminate\Support\Manager;
use GuzzleHttp\Psr7\HttpFactory;
use Droath\NextusAi\Drivers\Claude;
use Droath\NextusAi\Drivers\Openai;
use Droath\NextusAi\Drivers\Perplexity;
use SoftCreatR\PerplexityAI\PerplexityAI;
use Droath\NextusAi\Drivers\Enums\LlmProvider;

/**
 * Define the Nextus AI client class for managing LLM provider connections.
 */
class NextusAiClient extends Manager
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultDriver(): string
    {
        return LlmProvider::OPENAI->value;
    }

    /**
     * Create the OpenAI client class.
     */
    protected function createOpenaiDriver(): Openai
    {
        $client = \OpenAI::factory()
            ->withApiKey(config('nextus-ai.openai.api_key'))
            ->withBaseUri(config('nextus-ai.openai.base_url'))
            ->withOrganization(config('nextus-ai.openai.organization'))
            ->make();

        return new Openai($client);
    }

    /**
     * Create the Perplexity client class.
     */
    protected function createPerplexityDriver(): Perplexity
    {
        $httpClient = new Client();
        $httpFactory = new HttpFactory();

        return new Perplexity(
            new PerplexityAI(
                $httpFactory,
                $httpFactory,
                $httpFactory,
                $httpClient,
                config('nextus-ai.perplexity.api_key')
            )
        );
    }

    /**
     * Create the Claude client class.
     */
    protected function createClaudeDriver(): Claude
    {
        $client = Anthropic::client(config('nextus-ai.claude.api_key'));

        return new Claude($client);
    }
}
