<?php

declare(strict_types=1);

namespace Droath\NextusAi\Responses;

/**
 * Define the Nextus AI response embeddings.
 */
final readonly class NextusAiResponseEmbeddings
{
    private function __construct(
        public array $embeddings,
    ) {}

    public static function fromArray(array $embeddings): self
    {
        return new self($embeddings);
    }
}
