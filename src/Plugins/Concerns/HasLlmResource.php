<?php

declare(strict_types=1);

namespace Droath\NextusAi\Plugins\Concerns;

use Droath\NextusAi\Facades\NextusAi;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Resources\Contracts\ResourceInterface;

trait HasLlmResource
{
    /**
     * Get the LLM resource instance.
     */
    protected function resource(): ResourceInterface
    {
        return NextusAi::responses($this->provider());
    }

    /**
     * Get the LLM resource provider.
     */
    protected function provider(): LlmProvider
    {
        return $this->pluginDefinition['provider'] ?? LlmProvider::OPENAI;
    }
}
