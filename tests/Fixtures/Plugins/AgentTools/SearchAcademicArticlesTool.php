<?php

declare(strict_types=1);

namespace Droath\NextusAi\Tests\Fixtures\Plugins\AgentTools;

use Droath\NextusAi\Tools\ToolProperty;
use Droath\NextusAi\Plugins\AgentTool\AgentToolPlugin;
use Droath\NextusAi\Attributes\AgentToolPluginMetadata;

#[AgentToolPluginMetadata(
    id: 'search_academic_articles',
    label: 'Search Academic Articles',
    description: 'Search the latest academic articles.',
)]
class SearchAcademicArticlesTool extends AgentToolPlugin
{
    /**
     * {@inheritDoc}
     */
    public function execute(array $arguments): string
    {
        return 'Here is the latest information around your search query';
    }

    /**
     * {@inheritDoc}
     */
    public function properties(): array
    {
        return [
            ToolProperty::make('query', 'string')->required(),
        ];
    }
}
