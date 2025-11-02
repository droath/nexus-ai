<?php

declare(strict_types=1);

namespace Droath\NextusAi\Enums;

use Exception;
use Droath\NextusAi\Messages\MessageBase;
use Droath\NextusAi\Messages\UserMessage;
use Droath\NextusAi\Messages\SystemMessage;
use Droath\NextusAi\Messages\AssistantMessage;

/**
 * Define the standard LLM message roles.
 */
enum LlmRoles: string
{
    case TOOL = 'tool';
    case USER = 'user';
    case SYSTEM = 'system';
    case ASSISTANT = 'assistant';

    /**
     * @throws Exception
     */
    public static function createMessageFrom(
        string $role,
        array $values = []
    ): MessageBase {
        $role = self::tryFrom($role);

        return match ($role) {
            self::USER => UserMessage::fromValue($values),
            self::SYSTEM => SystemMessage::fromValue($values),
            self::ASSISTANT => AssistantMessage::fromValue($values),
            default => throw new Exception('Unexpected match value'),
        };
    }
}
