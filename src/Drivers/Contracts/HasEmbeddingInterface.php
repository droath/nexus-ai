<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers\Contracts;

use Droath\NextusAi\Resources\Contracts\EmbeddingsResourceInterface;

/**
 * Define the embedding interface.
 */
interface HasEmbeddingInterface
{
    /**
     * Define the embeddings resource.
     */
    public function embeddings(): EmbeddingsResourceInterface;
}
