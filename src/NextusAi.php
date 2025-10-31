<?php

namespace Droath\NextusAi;

use Droath\NextusAi\Drivers\Contracts\HasChatInterface;
use Droath\NextusAi\Drivers\Contracts\HasEmbeddingInterface;
use Droath\NextusAi\Drivers\Contracts\HasResponsesInterface;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Facades\NextusAiClient;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\EmbeddingsResourceInterface;
use Droath\NextusAi\Resources\Contracts\ResponsesResourceInterface;
use Droath\NextusAi\Testing\NextusAiFake;

/**
 * Define the Nextus AI class.
 */
class NextusAi
{
    public function fake(
        ?\Closure $responseCallback = null,
        ?\Closure $resourceCallback = null
    ): NextusAiFake {
        return new NextusAiFake($responseCallback, $resourceCallback);
    }

    /**
     * Interact with the LLM chat resource.
     */
    public function chat(LlmProvider $provider): ChatResourceInterface
    {
        /** @var \Droath\NextusAi\Drivers\Contracts\DriverInterface $driver */
        $driver = NextusAiClient::driver($provider->value);

        if (! $driver instanceof HasChatInterface) {
            throw new \RuntimeException(
                'The driver does not support the chat resource.'
            );
        }

        return $driver->chat();
    }

    public function responses(LlmProvider $provider): ResponsesResourceInterface
    {
        $driver = NextusAiClient::driver($provider->value);

        if (! $driver instanceof HasResponsesInterface) {
            throw new \RuntimeException(
                'The driver does not support the response resource.'
            );
        }

        return $driver->responses();
    }

    /**
     * Interact with the LLM embeddings resource.
     */
    public function embeddings(LlmProvider $provider): EmbeddingsResourceInterface
    {
        /** @var \Droath\NextusAi\Drivers\Contracts\DriverInterface $driver */
        $driver = NextusAiClient::driver($provider->value);

        if (! $driver instanceof HasEmbeddingInterface) {
            throw new \RuntimeException(
                'The driver does not support the embeddings resource.'
            );
        }

        return $driver->embeddings();
    }
}
