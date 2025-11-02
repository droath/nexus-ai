<?php

declare(strict_types=1);

namespace Droath\NextusAi\Attributes;

use Attribute;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\PluginManager\Attributes\PluginMetadata;

#[Attribute(Attribute::TARGET_CLASS)]
class AgentWorkerPluginMetadata extends PluginMetadata
{
    /**
     * Define the attribute constructor.
     *
     * @param string $id
     *   The plugin identifier.
     * @param string $label
     *   The plugin human-readable name.
     */
    public function __construct(
        string $id,
        string $label,
        protected LlmProvider $provider,
        protected array $tools = []
    ) {
        parent::__construct($id, $label);
    }
}
