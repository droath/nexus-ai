<?php

declare(strict_types=1);

namespace Droath\NextusAi\Messages;

class MessageContext
{
    private function __construct(
        public string $content,
        public array $metadata
    ) {}

    public static function make(
        string $content,
        array $metadata = []
    ): self {
        return new self($content, $metadata);
    }

    /**
     * @return mixed|null
     */
    public function getMetadataValue(
        string $key,
        $default = null
    ): mixed {
        return $this->metadata[$key] ?? $default;
    }
}
