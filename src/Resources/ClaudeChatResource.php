<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources;

use Anthropic\Contracts\ClientContract;
use Anthropic\Responses\Messages\CreateResponse;
use Anthropic\Responses\Messages\StreamResponse;
use Droath\NextusAi\Drivers\Claude;
use Anthropic\Responses\Messages\CreateResponseContent;
use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use Droath\NextusAi\Enums\LlmRoles;
use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Resources\Concerns\WithMessages;
use Droath\NextusAi\Resources\Concerns\WithModel;
use Anthropic\Responses\Messages\CreateStreamedResponse;
use Droath\NextusAi\Resources\Concerns\WithResponseFormat;
use Droath\NextusAi\Resources\Concerns\WithTools;
use Droath\NextusAi\Drivers\Concerns\HasStreaming;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\HasDriverInterface;
use Droath\NextusAi\Resources\Contracts\HasMessagesInterface;
use Droath\NextusAi\Resources\Contracts\HasResponseFormatInterface;
use Droath\NextusAi\Resources\Contracts\HasToolsInterface;
use Droath\NextusAi\Drivers\Contracts\HasStreamingInterface;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Illuminate\Support\Facades\Log;

/**
 * Claude Chat Resource for handling conversations with Anthropic's Claude API.
 *
 * This resource provides functionality for both streaming and synchronous chat
 * responses, tool calling, and proper error handling for the Claude API.
 */
class ClaudeChatResource extends ResourceBase implements ChatResourceInterface, HasDriverInterface, HasMessagesInterface, HasResponseFormatInterface, HasStreamingInterface, HasToolsInterface
{
    use HasStreaming;
    use WithMessages;
    use WithModel;
    use WithResponseFormat;
    use WithTools;

    protected string $model = Claude::DEFAULT_MODEL;

    public function __construct(
        protected ClientContract $client,
        protected DriverInterface $driver
    ) {}

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?NextusAiResponseMessage
    {
        $parameters = $this->resourceParameters();

        if (empty($parameters)) {
            return null;
        }

        return $this->handleResponse(
            $this->createResourceResponse($parameters)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function driver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Create the chat resource response using the Anthropic client.
     *
     * @param array $parameters The API parameters
     *
     * @return StreamResponse|CreateResponse The API response
     */
    protected function createResourceResponse(
        array $parameters
    ): StreamResponse|CreateResponse {
        return ! $this->stream
            ? $this->client->messages()->create($parameters)
            : $this->client->messages()->createStreamed($parameters);
    }

    /**
     * Handle all chat responses with proper error handling.
     *
     * @param object $response The API response object
     *
     * @return NextusAiResponseMessage|null The processed response message
     */
    protected function handleResponse(object $response): ?NextusAiResponseMessage
    {
        try {
            return match (true) {
                $response instanceof StreamResponse => $this->handleStream($response),
                $response instanceof CreateResponse => $this->handleSynchronous($response),
                default => throw new \RuntimeException('Unexpected response type')
            };
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Handle synchronous (non-streaming) chat responses.
     *
     * @param CreateResponse $response The synchronous response
     *
     * @return NextusAiResponseMessage|null The processed response message
     */
    protected function handleSynchronous(
        CreateResponse $response
    ): ?NextusAiResponseMessage {
        if ($this->hasToolUse($response)) {
            $toolCalls = collect($response->content)
                ->filter(fn ($content) => $content->type === 'tool_use')
                ->toArray();

            return $this->handleResponse(
                $this->handleToolCall($toolCalls)
            );
        }

        $content = $response->content[0]->text ?? null;

        if ($content === null) {
            return null;
        }

        return NextusAiResponseMessage::fromString($content);
    }

    /**
     * Handle streaming chat responses with real-time processing.
     *
     * @param StreamResponse $stream The streaming response
     *
     * @return NextusAiResponseMessage|null The final processed response
     *
     * @throws \JsonException
     */
    protected function handleStream(
        StreamResponse $stream
    ): ?NextusAiResponseMessage {
        $streamContent = '';
        $streamToolUse = [];

        foreach ($stream as $response) {
            $deltaStopReason = $response->delta->stop_reason;

            $streamContent = $this->processStreamContent(
                $response,
                $streamContent
            );

            $streamToolUse = $this->processStreamToolUse(
                $response,
                $streamToolUse
            );

            if ($this->isStreamMessageComplete($response)) {
                if ($deltaStopReason === 'tool_use' && ! empty($streamToolUse)) {
                    foreach ($streamToolUse as &$toolUse) {
                        $toolUse['input'] = json_decode(
                            $toolUse['input'],
                            true,
                            512,
                            JSON_THROW_ON_ERROR
                        );
                        $toolUse = CreateResponseContent::from($toolUse);
                    }

                    return $this->handleResponse(
                        $this->handleToolCall($streamToolUse)
                    );
                }

                if ($deltaStopReason === 'end_turn' && ! empty($streamContent)) {
                    $streamFinished = $this->streamFinished;
                    $streamResponse = NextusAiResponseMessage::fromString($streamContent);

                    if (is_callable($streamFinished)) {
                        $streamFinished($streamResponse);
                    }

                    return $streamResponse;
                }
            }
        }

        return null;
    }

    /**
     * Determine if the streaming message is complete.
     *
     * @param CreateStreamedResponse $response The streaming response chunk
     *
     * @return bool True if the message is complete
     */
    protected function isStreamMessageComplete(
        CreateStreamedResponse $response
    ): bool {
        return $response->type === 'message_stop' || (
            $response->type === 'message_delta'
            && isset($response->delta->stop_reason)
        );
    }

    /**
     * Process streaming content chunks and accumulate text.
     *
     * @param CreateStreamedResponse $response The streaming response chunk
     * @param string $streamContent The current accumulated content
     *
     * @return string The updated accumulated content
     */
    protected function processStreamContent(
        CreateStreamedResponse $response,
        string $streamContent
    ): string {
        if (
            $response->type === 'content_block_delta'
            && ($delta = $response->delta ?? null)
            && ($text = $delta->text ?? null)
        ) {
            $processorMethod = $this->useStreamBuffer
                ? 'handleStreamBufferProcess'
                : 'handleStreamProcess';

            if (method_exists($this, $processorMethod)) {
                $this->$processorMethod(
                    $text,
                    $streamContent
                );
            }

            $streamContent .= $text;
        }

        return $streamContent;
    }

    /**
     * Handle standard streaming process with chunk-by-chunk processing.
     *
     * @param string $chunk The text chunk received
     * @param string $streamContent The current accumulated content
     */
    protected function handleStreamProcess(
        string $chunk,
        string $streamContent
    ): void {
        if (isset($this->streamProcess)) {
            $partial = $chunk;
            $initialized = empty($streamContent);

            ($this->streamProcess)(
                $partial,
                $initialized,
            );
        }
    }

    /**
     * Handle buffered streaming process with custom buffer logic.
     *
     * @param string $chunk The text chunk received
     * @param string $streamContent The current accumulated content
     */
    protected function handleStreamBufferProcess(
        string $chunk,
        string $streamContent
    ): void {
        if (
            isset($this->streamBufferProcess)
            && ($this->streamBufferProcess)(
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
     * Process stream tool use.
     *
     * @param CreateStreamedResponse $response
     *   The streamed response object
     * @param array $toolUseBlocks
     *   The current tool use blocks
     *
     * @return array
     *   The updated tool use blocks
     */
    protected function processStreamToolUse(
        CreateStreamedResponse $response,
        array $toolUseBlocks
    ): array {
        $blockIndex = $response->index ?? 0;

        if (
            $response->type === 'content_block_start'
            && isset($response->content_block_start)
        ) {
            $contentBlock = $response->content_block_start;

            if ($contentBlock->type === 'tool_use') {
                $toolUseBlocks[$blockIndex] = [
                    'type' => 'tool_use',
                    'id' => $contentBlock->id ?? null,
                    'name' => $contentBlock->name ?? null,
                    'input' => '',
                ];
            }
        }
        $delta = $response->delta;

        if (
            $response->type === 'content_block_delta'
            && $delta->type === 'input_json_delta'
            && $delta->partial_json
        ) {
            $toolUseBlocks[$blockIndex]['input'] .= $delta->partial_json;
        }

        return $toolUseBlocks;
    }

    /**
     * Check if the response contains tool use requests.
     *
     * @param CreateResponse $response The API response
     *
     * @return bool True if the response contains tool calls
     */
    protected function hasToolUse(CreateResponse $response): bool
    {
        return ! empty($response->content) && collect($response->content)
            ->contains(fn ($content) => $content->type === 'tool_use');
    }

    /**
     * Handle tool calls by executing them and creating follow-up messages.
     *
     * @param CreateResponseContent[] $toolCalls Array of tool call objects
     *
     * @return CreateResponse|StreamResponse The response after tool execution
     */
    protected function handleToolCall(
        array $toolCalls
    ): CreateResponse|StreamResponse {
        $parameters = $this->resourceParameters();

        $parameters['system'] = 'Response based on the tool results.';

        foreach ($toolCalls as $toolResponse) {
            $parameters['messages'][] = [
                'role' => LlmRoles::ASSISTANT->value,
                'content' => [
                    $toolResponse->toArray(),
                ],
            ];
            $toolResult = $this->invokeTool($toolResponse);

            $parameters['messages'][] = [
                'role' => LlmRoles::USER->value,
                'content' => [
                    [
                        'type' => 'tool_result',
                        'tool_use_id' => $toolResponse->id,
                        'content' => $toolResult,
                    ],
                ],
            ];
        }

        return $this->createResourceResponse($parameters);
    }

    /**
     * Execute a specific tool call and return its result.
     *
     * @param CreateResponseContent $content The tool call content
     *
     * @return string|null The tool execution result
     */
    protected function invokeTool(CreateResponseContent $content): ?string
    {
        $tool = $this->tools->firstWhere('name', $content->name);

        if ($tool instanceof Tool) {
            $arguments = $content->input ?? [];

            return call_user_func_array($tool, $arguments);
        }

        return null;
    }

    /**
     * Build the parameter array for Claude API requests.
     *
     * @return array The API request parameters
     */
    protected function resourceParameters(): array
    {
        $messages = $this->resolveMessages();

        if (empty($messages)) {
            Log::warning('Claude API: No messages provided for request');

            return [];
        }
        [$userMessages, $systemMessage] = $this->parseMessages($messages);

        $parameters = [
            'model' => $this->model,
            'stream' => $this->stream,
            'system' => $systemMessage,
            'messages' => $userMessages,
            'max_tokens' => 4096,
        ];

        if ($tools = $this->resolveTools()) {
            $parameters['tools'] = $tools;
        }

        return array_filter($parameters);
    }

    /**
     * Parse messages array into user messages and system message.
     *
     * @param array $messages Array of message objects
     *
     * @return array Array containing [userMessages, systemMessage]
     */
    protected function parseMessages(array $messages): array
    {
        $userMessages = [];
        $systemMessage = null;

        foreach ($messages as $message) {
            if ($message['role'] === LlmRoles::SYSTEM->value) {
                $systemMessage = $message['content'];
            } else {
                $userMessages[] = $message;
            }
        }

        return [$userMessages, $systemMessage];
    }

    /**
     * Handle different types of exceptions from the Claude API with
     * appropriate logging.
     *
     * @param \Throwable $exception The exception to handle
     *
     * @return NextusAiResponseMessage|null Always returns null after logging
     */
    protected function handleException(
        \Throwable $exception
    ): ?NextusAiResponseMessage {
        $message = $exception->getMessage();
        $code = $exception->getCode();

        $context = [
            'code' => $code,
            'exception_type' => get_class($exception),
        ];

        // Handle rate limiting (429 status code)
        if ($code === 429 || str_contains($message, 'rate limit')) {
            Log::warning('Claude API rate limit exceeded', array_merge($context, ['message' => $message]));

            return null;
        }

        // Handle authentication errors (401 status code)
        if ($code === 401 || str_contains($message, 'unauthorized') || str_contains($message, 'invalid api key')) {
            Log::error('Claude API authentication error', array_merge($context, ['message' => $message]));

            return null;
        }

        // Handle quota/billing errors (403 status code)
        if ($code === 403 || str_contains($message, 'quota') || str_contains($message, 'billing')) {
            Log::error('Claude API quota/billing error', array_merge($context, ['message' => $message]));

            return null;
        }

        // Handle server errors (500+ status codes)
        if ($code >= 500) {
            Log::error('Claude API server error', array_merge($context, ['message' => $message]));

            return null;
        }

        // Handle validation errors (400 status code)
        if ($code === 400 || str_contains($message, 'validation') || str_contains($message, 'invalid')) {
            Log::error('Claude API validation error', array_merge($context, ['message' => $message]));

            return null;
        }

        // Log any other unexpected errors
        Log::error('Claude API unexpected error', array_merge($context, ['message' => $message]));

        return null;
    }
}
