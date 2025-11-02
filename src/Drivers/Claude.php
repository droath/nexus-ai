<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers;

use Droath\NextusAi\Tools\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Anthropic\Contracts\ClientContract;
use Droath\NextusAi\Resources\ClaudeChatResource;
use Droath\NextusAi\Drivers\Contracts\HasChatInterface;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;

/**
 * Define the Claude driver for the Nextus AI LLM client.
 */
class Claude extends NextusAiDriver implements HasChatInterface
{
    /** @var string */
    public const string DEFAULT_MODEL = 'claude-3-haiku-20240307';

    public function __construct(
        protected ClientContract $client
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function transformTool(Tool $tool): array
    {
        $data = $tool->toArray();

        $properties = [];
        if ($data['properties'] instanceof Collection) {
            foreach ($data['properties'] as $property) {
                $propData = $property->toArray();
                $properties[$propData['name']] = [
                    'type' => $propData['type'],
                    'description' => $propData['description'],
                ];

                if (! empty($propData['enum'])) {
                    $properties[$propData['name']]['enum'] = $propData['enum'];
                }
            }
        }

        return [
            'name' => $data['name'],
            'description' => $data['description'],
            'input_schema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $data['required'] ?? [],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function transformUserMessage(string $content): string|array
    {
        return $content;
    }

    /**
     * Get the Claude client instance.
     */
    public function client(): ClientContract
    {
        return $this->client;
    }

    /**
     * {@inheritDoc}
     */
    public function chat(): ChatResourceInterface
    {
        return new ClaudeChatResource($this->client, $this);
    }

    /**
     * Validate the Claude configuration
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        $apiKey = config('nextus-ai.claude.api_key');

        if (empty($apiKey)) {
            $errors[] = 'Claude API key is not configured. Set ANTHROPIC_API_KEY environment variable.';
            Log::warning('Claude driver: API key not configured');
        } elseif (! $this->isValidApiKeyFormat($apiKey)) {
            $errors[] = 'Claude API key format is invalid. Should start with "sk-ant-".';
            Log::error('Claude driver: Invalid API key format provided');
        }

        return $errors;
    }

    /**
     * Validate a model name format (basic validation only)
     */
    public function validateModel(string $model): array
    {
        $errors = [];

        if (empty($model)) {
            $errors[] = 'Model name cannot be empty.';
            Log::warning('Claude driver: Empty model name provided for validation');
        } elseif (! $this->isValidModelFormat($model)) {
            $errors[] = "Model '{$model}' has an invalid format. Expected format like 'claude-3-sonnet-20240229'.";
            Log::warning("Claude driver: Invalid model format provided: {$model}");
        }

        return $errors;
    }

    /**
     * Check if the API key has the correct format
     */
    protected function isValidApiKeyFormat(string $apiKey): bool
    {
        return str_starts_with($apiKey, 'sk-ant-') && mb_strlen($apiKey) > 10;
    }

    /**
     * Check if the model name has a basic valid format
     */
    protected function isValidModelFormat(string $model): bool
    {
        return str_starts_with($model, 'claude-') && mb_strlen($model) >= 10 && mb_strlen($model) <= 50;
    }
}
