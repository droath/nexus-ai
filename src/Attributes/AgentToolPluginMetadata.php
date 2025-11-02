<?php

declare(strict_types=1);

namespace Droath\NextusAi\Attributes;

use Attribute;
use Droath\PluginManager\Attributes\PluginMetadata;

#[Attribute(Attribute::TARGET_CLASS)]
class AgentToolPluginMetadata extends PluginMetadata
{
    /**
     * Define the attribute constructor.
     *
     * @param string $id
     *   The plugin identifier.
     * @param string $label
     *   The plugin human-readable name.
     * @param ?string $description
     *   The tool plugin description.
     */
    public function __construct(
        string $id,
        string $label,
        protected ?string $description = null,
    ) {
        parent::__construct($id, $label);
    }
}
