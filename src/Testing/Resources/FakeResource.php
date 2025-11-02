<?php

declare(strict_types=1);

namespace Droath\NextusAi\Testing\Resources;

use Droath\NextusAi\Drivers\Concerns\HasStreaming;
use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use Droath\NextusAi\Drivers\Contracts\HasStreamingInterface;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Drivers\Openai;
use Droath\NextusAi\Resources\Concerns\WithMessages;
use Droath\NextusAi\Resources\Concerns\WithModel;
use Droath\NextusAi\Resources\Concerns\WithResponseFormat;
use Droath\NextusAi\Resources\Concerns\WithTools;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\HasDriverInterface;
use Droath\NextusAi\Resources\Contracts\HasMessagesInterface;
use Droath\NextusAi\Resources\Contracts\HasResponseFormatInterface;
use Droath\NextusAi\Resources\Contracts\HasToolsInterface;
use Droath\NextusAi\Resources\Contracts\HasToolTransformerInterface;
use Droath\NextusAi\Resources\Contracts\StructuredResourceInterface;
use Droath\NextusAi\Resources\ResourceBase;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Tools\Tool;
use OpenAI\Testing\ClientFake;

class FakeResource extends ResourceBase implements ChatResourceInterface, HasDriverInterface, HasMessagesInterface, HasResponseFormatInterface, HasStreamingInterface, HasToolsInterface, HasToolTransformerInterface, StructuredResourceInterface
{
    use HasStreaming;
    use WithMessages;
    use WithModel;
    use WithResponseFormat;
    use WithTools;

    /** @var array<string, mixed> */
    public array $invokedParameters = [];

    protected ?string $model = null;

    public function __construct(
        protected LlmProvider $provider,
        protected ?\Closure $responseHandler = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function transformTool(Tool $tool): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?NextusAiResponseMessage
    {
        $this->invokedParameters = [
            'tools' => $this->resolveTools(),
            'messages' => $this->resolveMessages(),
            'response_format' => $this->responseFormat,
        ];

        return $this->responseHandler->__invoke();
    }

    /**
     * {@inheritDoc}
     */
    public function driver(): DriverInterface
    {
        return match ($this->provider) {
            LlmProvider::OPENAI => new Openai(
                /** @phpstan-ignore-next-line Fake resource for testing */
                new ClientFake([$this])
            ),
            LlmProvider::CLAUDE, LlmProvider::PERPLEXITY => throw new \RuntimeException(
                "Fake driver for {$this->provider->value} not implemented yet"
            ),
        };
    }
}
