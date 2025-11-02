<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers;

use Illuminate\Support\Str;
use OpenAI\Testing\ClientFake;
use Droath\NextusAi\Tools\Tool;
use OpenAI\Contracts\ClientContract;
use Droath\NextusAi\Tools\ToolProperty;
use Droath\NextusAi\Resources\OpenaiChatResource;
use Droath\NextusAi\Resources\OpenaiEmbeddingResource;
use Droath\NextusAi\Resources\OpenaiStructuredResource;
use Droath\NextusAi\Drivers\Contracts\HasChatInterface;
use Droath\NextusAi\Drivers\Contracts\HasEmbeddingInterface;
use Droath\NextusAi\Drivers\Contracts\HasStructuredInterface;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\StructuredResourceInterface;
use Droath\NextusAi\Resources\Contracts\EmbeddingsResourceInterface;

/**
 * Define the OpenAI driver for the Nextus AI LLM client.
 */
class Openai extends NextusAiDriver implements HasChatInterface, HasEmbeddingInterface, HasStructuredInterface
{
    /** @var string */
    public const string DEFAULT_MODEL = 'gpt-4o-mini';

    /** @var string */
    public const string DEFAULT_EMBEDDING_MODEL = 'text-embedding-3-small';

    public function __construct(
        protected ClientContract $client
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function transformTool(Tool $tool): array
    {
        $data = $tool->toArray();

        $definition = [
            'type' => 'function',
            'name' => $data['name'],
            'strict' => $data['strict'] ?? false,
        ];

        if ($tool->hasProperties()) {
            $definition['parameters'] = [
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
    public static function transformUserMessage(string $content): string|array
    {
        if (Str::isJson($content)) {
            try {
                $contents = [];
                $parts = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                foreach ($parts as $value) {
                    if (! is_string($value)) {
                        continue;
                    }

                    if (Str::startsWith($value, 'data:text')) {
                        $contents[] = [
                            'type' => 'text',
                            'text' => static::decodeBase64DataUri($value),
                        ];
                    }

                    if (Str::startsWith($value, 'data:image')) {
                        $contents[] = [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $value,
                            ],
                        ];
                    }

                    if (Str::startsWith($value, ['data:application'])) {
                        $contents[] = [
                            'type' => 'file',
                            'file' => [
                                'file_data' => $value,
                            ],
                        ];
                    }
                }
            } catch (\JsonException) {
                return [];
            }

            return ! empty($contents)
                ? $contents
                : $content;
        }

        return $content;
    }

    protected static function decodeBase64DataUri(string $uri): ?string
    {
        preg_match('/^data:([^;]+);base64,(.+)$/i', $uri, $matches);

        array_shift($matches);

        if (! empty($matches)) {
            [$mimeType, $data] = $matches;

            return base64_decode($data);
        }

        return null;
    }

    public function client(): ?ClientContract
    {
        return $this->client instanceof ClientFake
            ? $this->client
            : null;
    }

    /**
     * {@inheritDoc}
     */
    public function chat(): ChatResourceInterface
    {
        return new OpenaiChatResource(
            $this->client->chat(),
            $this
        );
    }

    /**
     * {@inheritDoc}
     */
    public function structured(): StructuredResourceInterface
    {
        return new OpenaiStructuredResource(
            $this->client->responses(),
            $this
        );
    }

    /**
     * {@inheritDoc}
     */
    public function embeddings(): EmbeddingsResourceInterface
    {
        return new OpenaiEmbeddingResource(
            $this->client->embeddings(),
            $this
        );
    }
}
