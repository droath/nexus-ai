<?php

declare(strict_types=1);

namespace Droath\NextusAi\Testing\Support;

use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Testing\Enums\OverrideStrategy;

/** @phpstan-ignore trait.unused */
trait ResourceResponsesHelper
{
    /**
     * Create a fake text response.
     */
    protected function createFakeTextResponse(
        string $text,
        ?string $messageId = null,
        OverrideStrategy $strategy = OverrideStrategy::Replace
    ): CreateResponse {
        return CreateResponse::fake([
            'model' => 'gpt-4o-mini',
            'output' => [
                [
                    'type' => 'message',
                    'id' => $messageId ?? 'msg_'.uniqid('', true),
                    'status' => 'completed',
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => $text,
                            'annotations' => [],
                        ],
                    ],
                ],
            ],
        ], strategy: $strategy);
    }

    /**
     * Create a fake JSON response.
     *
     * @throws \JsonException
     */
    protected function createFakeJsonResponse(
        array $data,
        ?string $messageId = null,
        OverrideStrategy $strategy = OverrideStrategy::Replace
    ): CreateResponse {
        return CreateResponse::fake([
            'model' => 'gpt-4o-mini',
            'output' => [
                [
                    'type' => 'message',
                    'id' => $messageId ?? 'msg_'.uniqid('', true),
                    'status' => 'completed',
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => json_encode($data, JSON_THROW_ON_ERROR),
                            'annotations' => [],
                        ],
                    ],
                ],
            ],
        ], strategy: $strategy);
    }
}
