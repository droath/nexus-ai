<?php

declare(strict_types=1);

namespace Droath\NextusAi;

use Closure;
use RuntimeException;
use Droath\NextusAi\Testing\NextusAiFake;
use Droath\NextusAi\Facades\NextusAiClient;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Drivers\Contracts\HasChatInterface;
use Droath\NextusAi\Drivers\Contracts\HasEmbeddingInterface;
use Droath\NextusAi\Drivers\Contracts\HasStructuredInterface;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\EmbeddingsResourceInterface;
use Droath\NextusAi\Resources\Contracts\StructuredResourceInterface;

/**
 * Define the Nextus AI class.
 */
class NextusAi
{
    public function fake(
        ?Closure $responseCallback = null,
        ?Closure $resourceCallback = null
    ): NextusAiFake {
        return new NextusAiFake($responseCallback, $resourceCallback);
    }

    /**
     * Interact with the LLM chat resource.
     */
    public function chat(LlmProvider $provider): ChatResourceInterface
    {
        /** @var Drivers\Contracts\DriverInterface $driver */
        $driver = NextusAiClient::driver($provider->value);

        if (! $driver instanceof HasChatInterface) {
            throw new RuntimeException(
                'The driver does not support the chat resource.'
            );
        }

        return $driver->chat();
    }

    public function structured(LlmProvider $provider): StructuredResourceInterface
    {
        $driver = NextusAiClient::driver($provider->value);

        if (! $driver instanceof HasStructuredInterface) {
            throw new RuntimeException(
                'The driver does not support the structured resource.'
            );
        }

        return $driver->structured();
    }

    /**
     * Interact with the LLM embeddings resource.
     */
    public function embeddings(LlmProvider $provider): EmbeddingsResourceInterface
    {
        /** @var Drivers\Contracts\DriverInterface $driver */
        $driver = NextusAiClient::driver($provider->value);

        if (! $driver instanceof HasEmbeddingInterface) {
            throw new RuntimeException(
                'The driver does not support the embeddings resource.'
            );
        }

        return $driver->embeddings();
    }
}
