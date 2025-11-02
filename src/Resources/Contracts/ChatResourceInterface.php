<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

use Droath\NextusAi\Responses\NextusAiResponseMessage;

interface ChatResourceInterface extends ResourceInterface
{
    public function __invoke(): ?NextusAiResponseMessage;
}
