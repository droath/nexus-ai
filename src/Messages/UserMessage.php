<?php

declare(strict_types=1);

namespace Droath\NextusAi\Messages;

use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use Droath\NextusAi\Enums\LlmRoles;
use Droath\NextusAi\Messages\Concerns\ViewSupport;
use Droath\NextusAi\Messages\Contracts\MessageDriverAwareInterface;

/**
 * Define the user message value object.
 */
final class UserMessage extends MessageBase implements MessageDriverAwareInterface
{
    use ViewSupport;

    protected ?DriverInterface $driver = null;

    private function __construct(
        public readonly string $content,
        public readonly ?MessageContext $context,
    ) {}

    /**
     * @return mixed
     */
    public static function make(
        string $content,
        null|string|MessageContext $context = null,
    ): UserMessage {
        return new self(
            $content,
            is_string($context)
                ? MessageContext::make($context)
                : $context
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function fromValue(array $value): self
    {
        return self::make(
            $value['content'],
            is_array($value['context']) ? MessageContext::make(
                $value['context']['content'],
                $value['context']['metadata'] ?? []
            ) : $value['context']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function toValue(): array
    {
        return [
            'role' => LlmRoles::USER->value,
            'content' => $this->content,
            'context' => $this->context,
        ];
    }

    public function hasContext(): bool
    {
        return ! empty($this->context);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'role' => LlmRoles::USER->value,
            'content' => $this->structureContent(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    protected function structureContent(): string|array
    {
        $content = $this->content;

        if (! empty($this->context->content)) {
            $content .= $this->context->content;
        }

        if ($this->driver instanceof DriverInterface) {
            return $this->driver::transformUserMessage(
                $content
            );
        }

        return $content;
    }
}
