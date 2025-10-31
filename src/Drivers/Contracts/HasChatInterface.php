<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers\Contracts;

use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;

/**
 * Define the chat interface.
 */
interface HasChatInterface
{
    /**
     * Define the chat resource.
     */
    public function chat(): ChatResourceInterface;
}
