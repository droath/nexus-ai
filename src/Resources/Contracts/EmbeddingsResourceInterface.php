<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

use Droath\NextusAi\Responses\NextusAiResponseEmbeddings;

interface EmbeddingsResourceInterface extends ResourceInterface
{
    public function __invoke(): NextusAiResponseEmbeddings;
}
