<?php

declare(strict_types=1);

namespace Droath\NextusAi\Messages;

use Droath\NextusAi\Enums\LlmRoles;
use Droath\NextusAi\Messages\Concerns\ViewSupport;

/**
 * Define the system message value object.
 */
final class SystemMessage extends MessageBase
{
    use ViewSupport;

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'role' => LlmRoles::SYSTEM->value,
            'content' => $this->content,
        ];
    }
}
