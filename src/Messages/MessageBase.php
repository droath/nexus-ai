<?php

declare(strict_types=1);

namespace Droath\NextusAi\Messages;

use Illuminate\Contracts\Support\Arrayable;

/** @phpstan-consistent-constructor */
abstract class MessageBase implements Arrayable
{
    protected function __construct(
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
