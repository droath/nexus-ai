<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers;

use SoftCreatR\PerplexityAI\PerplexityAI;
use Droath\NextusAi\Resources\PerplexityChatResource;
use Droath\NextusAi\Drivers\Contracts\HasChatInterface;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;

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
