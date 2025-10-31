<?php

declare(strict_types=1);

namespace Droath\NextusAi\Messages;

use Illuminate\Contracts\Support\Arrayable;

abstract class MessageBase implements Arrayable
{
    private function __construct(
        public readonly string $content,
    ) {}

    /**
     * @return self
     */
    public static function fromValue(array $value): mixed
    {
        return static::make(
            $value['content'],
        );
    }

    /**
     * @return static
     */
    public static function make(
        string $content,
    ): mixed {
        return new static($content);
    }

    public function toValue(): array
    {
        return $this->toArray();
    }
}
