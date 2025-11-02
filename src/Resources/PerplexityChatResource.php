<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources;

use JsonException;
use Droath\NextusAi\Drivers\Perplexity;
use Psr\Http\Message\ResponseInterface;
use SoftCreatR\PerplexityAI\PerplexityAI;
use Droath\NextusAi\Resources\Concerns\WithMessages;
use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Resources\Concerns\WithResponseFormat;
use Droath\NextusAi\Resources\Contracts\HasMessagesInterface;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\HasResponseFormatInterface;

/**
 * Define the Perplexity chat resource.
 */
class PerplexityChatResource extends ResourceBase implements ChatResourceInterface, HasMessagesInterface, HasResponseFormatInterface
{
    use WithMessages;
    use WithResponseFormat;

    protected string $model = Perplexity::DEFAULT_MODEL;

    public function __construct(
        protected PerplexityAI $client,
        protected DriverInterface $driver
    ) {}

    public function __invoke(): ?NextusAiResponseMessage
    {
        return $this->handleResponse();
    }

    /**
     * {@inheritDoc}
     */
    public function withModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    protected function handleResponse(): ?NextusAiResponseMessage
    {
        $response = $this->client->createChatCompletion(
            [],  // First arg: URL path parameters (empty for this endpoint)
            $this->resourceParameters()  // Second arg: body parameters
        );

        if ($response->getStatusCode() === 200) {
            return NextusAiResponseMessage::fromArray(
                'choices.0.message.content',
                $this->formatJsonFromResponse(
                    $response
                )
            );
        }

        return null;
    }

    /**
     * Define the chat resource parameters.
     */
    protected function resourceParameters(): array
    {
        return array_filter([
            'model' => $this->model,
            'messages' => $this->mergeConsecutiveMessages(),
        ]);
    }

    /**
     * Merge consecutive messages.
     *
     * This is a workaround for Perplexity API, which doesn't allow consecutive
     * messages with the same role.
     */
    protected function mergeConsecutiveMessages(): array
    {
        return collect($this->resolveMessages())
            ->reduce(function ($carry, $message) {
                if (empty($carry) || end($carry)['role'] !== $message['role']) {
                    $carry[] = $message;
                } else {
                    $lastIndex = count($carry) - 1;
                    $carry[$lastIndex]['content'] .= "\r\n{$message['content']}";
                }

                return $carry;
            }, []);
    }

    protected function formatJsonFromResponse(ResponseInterface $response): array
    {
        $content = $response->getBody()->getContents();

        try {
            return json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return [];
        }
    }
}
