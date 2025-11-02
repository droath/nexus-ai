<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Concerns;

use Illuminate\Support\Arr;
use Droath\NextusAi\Messages\SystemMessage;
use Illuminate\Contracts\Support\Arrayable;
use Droath\NextusAi\Resources\Contracts\HasDriverInterface;
use Droath\NextusAi\Messages\Contracts\MessageDriverAwareInterface;

trait WithMessages
{
    protected array $messages;

    /**
     * {@inheritDoc}
     */
    public function withMessages(array $messages): static
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function pushSystemMessage(SystemMessage|string $message): static
    {
        $this->messages = Arr::prepend(
            $this->messages,
            is_string($message) ? SystemMessage::make($message) : $message,
        );

        return $this;
    }

    /**
     * Resolve the resource messages.
     */
    protected function resolveMessages(): array
    {
        $messages = $this->messages;
        foreach ($messages as &$message) {
            if (
                $this instanceof HasDriverInterface
                && $message instanceof MessageDriverAwareInterface
            ) {
                $message->setDriver($this->driver());
            }
            if ($message instanceof Arrayable) {
                $message = $message->toArray();
            }
        }

        return array_filter($messages);
    }
}
