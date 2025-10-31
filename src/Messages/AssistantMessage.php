<?php

declare(strict_types=1);

namespace Droath\NextusAi\Messages;

use Droath\NextusAi\Enums\LlmRoles;

/**
 * Define the assistant message value object.
 */
final class AssistantMessage extends MessageBase
{
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'role' => LlmRoles::ASSISTANT->value,
            'content' => $this->content,
        ];
    }
}
