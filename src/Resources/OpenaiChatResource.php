<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources;

use Illuminate\Support\Arr;
use Droath\NextusAi\Tools\Tool;
use Illuminate\Support\Facades\Log;
use OpenAI\Responses\StreamResponse;
use Droath\NextusAi\Drivers\Openai;
use Droath\NextusAi\Enums\LlmRoles;
use Droath\NextusAi\Tools\ToolProperty;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Contracts\Resources\ChatContract;
use OpenAI\Responses\Chat\CreateResponseChoice;
use OpenAI\Responses\Chat\CreateResponseToolCall;
use OpenAI\Responses\Chat\CreateStreamedResponse;
use Droath\NextusAi\Resources\Concerns\WithModel;
use Droath\NextusAi\Resources\Concerns\WithTools;
use Droath\NextusAi\Drivers\Concerns\HasStreaming;
use Droath\NextusAi\Resources\Concerns\WithMessages;
use OpenAI\Responses\Chat\CreateStreamedResponseChoice;
use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use OpenAI\Responses\Chat\CreateStreamedResponseToolCall;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Resources\Concerns\WithResponseFormat;
use Droath\NextusAi\Resources\Contracts\HasToolsInterface;
use Droath\NextusAi\Resources\Contracts\HasDriverInterface;
use Droath\NextusAi\Drivers\Contracts\HasStreamingInterface;
use Droath\NextusAi\Resources\Contracts\HasMessagesInterface;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\HasResponseFormatInterface;
use Droath\NextusAi\Resources\Contracts\HasToolTransformerInterface;

/**
 * Define the OpenAI chat resource.
 */
class OpenaiChatResource implements ChatResourceInterface, HasDriverInterface, HasMessagesInterface, HasResponseFormatInterface, HasStreamingInterface, HasToolsInterface, HasToolTransformerInterface
{
    protected string $model = Openai::DEFAULT_MODEL;

    use HasStreaming;
    use WithMessages;
    use WithModel;
    use WithResponseFormat;
    use WithTools;

    public function __construct(
        protected ChatContract $resource,
        protected DriverInterface $driver
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function transformTool(Tool $tool): array
    {
        $data = $tool->toArray();

        $definition = [
            'type' => 'function',
            'function' => [
                'name' => $data['name'],
                'strict' => $data['strict'] ?? false,
            ],
        ];

        if ($tool->hasProperties()) {
            $definition['function']['parameters'] = [
                'type' => 'object',
                'properties' => $data['properties']
                    ->flatMap(function (ToolProperty $property) {
                        $data = $property->toArray();

                        if ($name = $data['name']) {
                            return [
                                $name => array_filter([
                                    'type' => $data['type'],
                                    'enum' => $data['enum'],
                                    'description' => $data['description'],
                                ]),
                            ];
                        }

                        return [];
                    })->toArray(),
                'required' => $data['required'] ?? [],
            ];
        }

        return $definition;
    }

    /**
     * {@inheritDoc}
     */
    public function driver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?NextusAiResponseMessage
    {
        $parameters = $this->resourceParameters();

        return $this->handleResponse(
            $this->createResourceResponse($parameters)
        );
    }

    /**
     * Create the chat resource response.
     */
    protected function createResourceResponse(
        array $parameters
    ): StreamResponse|CreateResponse {
        return ! $this->stream
            ? $this->resource->create($parameters)
            : $this->resource->createStreamed($parameters);
    }

    /**
     * Process the chat stream content.
     */
    protected function processStreamContent(
        CreateStreamedResponse $response,
        ?string $streamContent
    ): ?string {
        if ($chunk = $response->choices[0]->delta->content) {
            $processorMethod = $this->useStreamBuffer
                ? 'handleStreamBufferProcess'
                : 'handleStreamProcess';

            if (method_exists($this, $processorMethod)) {
                $this->$processorMethod(
                    $chunk,
                    $streamContent
                );
            }

            $streamContent .= $chunk;
        }

        return $streamContent;
    }

    /**
     * Handle the standard stream process.
     */
    protected function handleStreamProcess(
        string $chunk,
        ?string $streamContent
    ): void {
        $streamProcess = $this->streamProcess;

        if (is_callable($streamProcess)) {
            $partial = $chunk;
            $initialized = is_null($streamContent);

            $streamProcess(
                $partial,
                $initialized,
            );
        }
    }

    /**
     * Handle the stream buffer process.
     */
    protected function handleStreamBufferProcess(
        string $chunk,
        ?string $streamContent
    ): void {
        $streamBufferProcess = $this->streamBufferProcess;

        if (
            is_callable($streamBufferProcess)
            && $streamBufferProcess(
                $chunk,
                $this->streamBuffer
            )
        ) {
            $partial = $this->streamBuffer.$chunk;

            $this->handleStreamProcess(
                $partial,
                $streamContent
            );

            $this->streamBuffer = null;
        } else {
            $this->streamBuffer .= $chunk;
        }
    }

    /**
     * Define the openai response parameters.
     */
    protected function resourceParameters(): array
    {
        return array_filter([
            'model' => $this->model,
            'tools' => $this->resolveTools(),
            'messages' => $this->resolveMessages(),
            'response_format' => $this->responseFormat,
        ]);
    }

    /**
     * Determine if the response is a tool call.
     */
    protected function isToolCall(CreateResponseChoice $choice): bool
    {
        return ($choice->finishReason === 'tool_calls')
            && ! empty($choice->message?->toolCalls ?? []);
    }

    /**
     * Invoke the chat tool from the response.
     *
     * @throws \JsonException
     */
    protected function invokeTool(
        CreateResponseToolCall|CreateStreamedResponseToolCall $toolCall
    ): ?string {
        $tool = $this->tools->firstWhere('name', $toolCall->function->name);

        if ($tool instanceof Tool) {
            $arguments = ! empty($toolCall->function->arguments)
                ? json_decode(
                    $toolCall->function->arguments,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ) : [];

            return call_user_func_array($tool, $arguments);
        }

        return null;
    }

    /**
     * Handle the chat tool calling.
     *
     * @throws \JsonException
     */
    protected function handleToolCall(
        CreateResponseChoice|CreateStreamedResponseChoice $choice
    ): CreateResponse|StreamResponse {
        $parameters = $this->resourceParameters();

        $choiceInstance = match (true) {
            $choice instanceof CreateResponseChoice => $choice->message,
            $choice instanceof CreateStreamedResponseChoice => $choice->delta
        };
        $parameters['messages'][] = $choiceInstance->toArray();

        foreach ($choiceInstance->toolCalls as $toolCall) {
            if ($toolCall->type !== 'function') {
                continue;
            }

            $parameters['messages'][] = [
                'role' => LlmRoles::TOOL->value,
                'content' => $this->invokeTool($toolCall),
                'tool_call_id' => $toolCall->id,
            ];
        }

        return $this->createResourceResponse($parameters);
    }

    /**
     * Handle all chat responses.
     */
    protected function handleResponse(object $response): ?NextusAiResponseMessage
    {
        try {
            return match (true) {
                $response instanceof StreamResponse => $this->handleStream($response),
                $response instanceof CreateResponse => $this->handleSynchronous($response),
                default => throw new \RuntimeException('Unexpected response type')
            };
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return null;
        }
    }

    /**
     * Handle the chat synchronous process.
     *
     * @throws \JsonException
     */
    protected function handleSynchronous(
        CreateResponse $response
    ): ?NextusAiResponseMessage {
        foreach ($response->choices as $choice) {
            if ($this->isToolCall($choice)) {
                $this->handleResponse(
                    $this->handleToolCall($choice)
                );
            }
        }

        return NextusAiResponseMessage::fromString(
            $response->choices[0]->message->content
        );
    }

    /**
     * Process the stream tool calls.
     */
    protected function processStreamToolCalls(
        CreateStreamedResponse $response,
        array $streamToolCalls
    ): array {
        if (empty($this->tools)) {
            return $streamToolCalls;
        }

        foreach ($response->choices as $choice) {
            if (empty($choice->delta->toolCalls)) {
                continue;
            }
            foreach ($choice->toArray() as $parentKey => $value) {
                if (is_array($value)) {
                    foreach (Arr::dot($value) as $nestedKey => $nestedValue) {
                        $prevValue = Arr::get($streamToolCalls, "$parentKey.$nestedKey", '');
                        $prevValue .= $nestedValue;
                        Arr::set(
                            $streamToolCalls,
                            "$parentKey.$nestedKey",
                            $prevValue
                        );
                    }
                } else {
                    $streamToolCalls[$parentKey] = $value;
                }
            }
        }

        return $streamToolCalls;
    }

    /**
     * Handle the chat stream process.
     *
     * @throws \JsonException
     */
    protected function handleStream(
        StreamResponse $stream,
    ): ?NextusAiResponseMessage {
        $streamContent = null;
        $streamToolCalls = [];

        /** @var \OpenAI\Responses\Chat\CreateStreamedResponse $response */
        foreach ($stream as $response) {
            $finishReason = $response->choices[0]->finishReason;

            $streamContent = $this->processStreamContent(
                $response,
                $streamContent
            );

            $streamToolCalls = $this->processStreamToolCalls(
                $response,
                $streamToolCalls
            );

            if ($finishReason === 'tool_calls'
                && ! empty($streamToolCalls)
                && ($choice = CreateStreamedResponseChoice::from($streamToolCalls))
            ) {
                $toolCallResponse = $this->handleToolCall($choice);

                return $this->handleResponse($toolCallResponse);
            }

            if ($finishReason === 'stop') {
                $streamFinished = $this->streamFinished;
                $streamResponse = NextusAiResponseMessage::fromString(
                    $streamContent
                );

                if (is_callable($streamFinished)) {
                    $streamFinished($streamResponse);
                }

                return $streamResponse;
            }
        }

        return null;
    }
}
