<?php

declare(strict_types=1);

namespace Droath\NextusAi\Responses;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Define the Nextus AI response message.
 */
final readonly class NextusAiResponseMessage
{
    private function __construct(
        public ?string $message,
    ) {}

    public static function fromString(string $message): self
    {
        return new self($message);
    }

    public static function fromArray(string $key, array $message): self
    {
        return new self(Arr::get($message, $key));
    }

    /**
     * @throws \JsonException
     */
    public function toArray(): array
    {
        if ($this->containsJson()) {
            return json_decode(
                $this->message,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return [];
    }

    public function containsJson(): bool
    {
        return Str::isJson($this->message);
    }

    public function __toString(): string
    {
        return $this->message ?? '';

    }
}
