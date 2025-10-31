<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources;

use Droath\NextusAi\Tools\Tool;
use Illuminate\Support\Facades\Log;
use OpenAI\Responses\StreamResponse;
use Droath\NextusAi\Drivers\Openai;
use Droath\NextusAi\Messages\UserMessage;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Contracts\Resources\ResponsesContract;
use Droath\NextusAi\Resources\Concerns\WithInput;
use Droath\NextusAi\Resources\Concerns\WithModel;
use Droath\NextusAi\Resources\Concerns\WithTools;
use Droath\NextusAi\Drivers\Concerns\HasStreaming;
use OpenAI\Responses\Responses\Output\OutputMessage;
use Droath\NextusAi\Resources\Concerns\WithMessages;
use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use OpenAI\Responses\Responses\Streaming\OutputTextDelta;
use Droath\NextusAi\Responses\NextusAiResponseMessage;
use Droath\NextusAi\Resources\Concerns\WithResponseFormat;
use Droath\NextusAi\Resources\Contracts\HasToolsInterface;
use Droath\NextusAi\Resources\Contracts\HasDriverInterface;
use OpenAI\Responses\Responses\Output\OutputFunctionToolCall;
use Droath\NextusAi\Drivers\Contracts\HasStreamingInterface;
use Droath\NextusAi\Resources\Contracts\HasMessagesInterface;
use Droath\NextusAi\Resources\Contracts\HasResponseFormatInterface;
use Droath\NextusAi\Resources\Contracts\ResponsesResourceInterface;

/**
 * Define the OpenAI responses resource.
 */
class OpenaiResponsesResource implements HasDriverInterface, HasMessagesInterface, HasResponseFormatInterface, HasStreamingInterface, HasToolsInterface, ResponsesResourceInterface
{
    protected string $model = Openai::DEFAULT_MODEL;

    use HasStreaming;
    use WithInput;
    use WithMessages;
    use WithModel;
    use WithResponseFormat;
    use WithTools;

    public function __construct(
        protected ResponsesContract $resource,
        protected DriverInterface $driver
    ) {}

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
     * Create the resource response.
     */
    protected function createResourceResponse(
        array $parameters
    ): StreamResponse|CreateResponse {
        return ! $this->stream
            ? $this->resource->create($parameters)
            : $this->resource->createStreamed($parameters);
    }

    /**
     * Process the stream output text delta.
     */
    protected function processStreamContent(
        OutputTextDelta $textDelta,
        ?string $streamContent
    ): ?string {
        if ($chunk = $textDelta->delta) {
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
     * Define the resource parameters.
     */
    protected function resourceParameters(): array
    {
        $parameters = [
            'model' => $this->model,
            'input' => $this->resolveInput(),
            'tools' => $this->resolveTools(),
        ];

        if ($format = $this->responseFormat) {
            $parameters['text']['format'] = reset($format);
        }

        return array_filter($parameters);
    }

    protected function resolveInput(): array
    {
        return $this->input
            ? [UserMessage::make($this->input)->toArray()]
            : $this->resolveMessages();
    }

    /**
     * Invoke the resource function tool.
     *
     * @throws \JsonException
     */
    protected function invokeTool(
        OutputFunctionToolCall $toolCall
    ): null|string|NextusAiResponseMessage {
        $tool = $this->tools->firstWhere('name', $toolCall->name);

        if ($tool instanceof Tool) {
            $arguments = ! empty($toolCall->arguments)
                ? json_decode(
                    $toolCall->arguments,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ) : [];

            return call_user_func_array($tool, $arguments);
        }

        return null;
    }

    /**
     * Handle the resource function tool calling.
     *
     * @throws \JsonException
     */
    protected function handleFunctionToolCall(
        OutputFunctionToolCall $output
    ): CreateResponse|StreamResponse {
        $parameters = $this->resourceParameters();

        $parameters['input'][] = $output->toArray();

        $parameters['input'][] = [
            'type' => 'function_call_output',
            'call_id' => $output->callId,
            'output' => $this->invokeTool($output),
        ];

        return $this->createResourceResponse($parameters);
    }

    /**
     * Handle the resource response.
     */
    protected function handleResponse(
        StreamResponse|CreateResponse $response
    ): ?NextusAiResponseMessage {
        try {
            return match (true) {
                $response instanceof StreamResponse => $this->handleStream($response),
                $response instanceof CreateResponse => $this->handleSynchronous($response),
                default => throw new \RuntimeException('Unexpected response instance')
            };
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return null;
        }
    }

    /**
     * Handle the resources synchronous process.
     */
    protected function handleSynchronous(
        CreateResponse $response
    ): ?NextusAiResponseMessage {
        foreach ($response->output as $output) {
            if (
                $output instanceof OutputFunctionToolCall
                && ($response = $this->handleFunctionToolCall($output))
            ) {
                $this->handleResponse($response);
            }
        }

        return NextusAiResponseMessage::fromString($response->outputText);
    }

    /**
     * Handle the resource stream process.
     *
     * @throws \JsonException
     */
    protected function handleStream(
        StreamResponse $stream,
    ): ?NextusAiResponseMessage {
        $streamContent = null;

        /** @var \OpenAI\Responses\Responses\CreateStreamedResponse $response */
        foreach ($stream as $response) {
            $event = $response->event;

            if ($event === 'response.output_text.delta') {
                $streamContent = $this->processStreamContent(
                    $response->response,
                    $streamContent
                );
            }

            if ($event === 'response.output_item.done') {
                $item = $response->response->item;

                if ($item instanceof OutputFunctionToolCall) {
                    return $this->handleResponse(
                        $this->handleFunctionToolCall($response->response->item)
                    );
                }

                if ($item instanceof OutputMessage) {
                    $text = $item->content[0]->text;

                    $streamResponse = NextusAiResponseMessage::fromString(
                        $text
                    );
                    $streamFinished = $this->streamFinished;

                    if (is_callable($streamFinished)) {
                        $streamFinished($streamResponse);
                    }

                    return $streamResponse;
                }
            }
        }

        return null;
    }
}
