<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers;

use Droath\NextusAi\Drivers\Contracts\HasChatInterface;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\PerplexityChatResource;
use SoftCreatR\PerplexityAI\PerplexityAI;

class Perplexity extends NextusAiDriver implements HasChatInterface
{
    /**
     * Define the default modal to use.
     */
    public const string DEFAULT_MODEL = 'sonar';

    public function __construct(
        protected PerplexityAI $client
    ) {}

    /**
     * {@inheritDoc}
     */
    public function chat(): ChatResourceInterface
    {
        return new PerplexityChatResource($this->client, $this);
    }
}
