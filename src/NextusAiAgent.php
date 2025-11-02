<?php

declare(strict_types=1);

namespace Droath\NextusAi;

use Throwable;
use Illuminate\Support\Facades\Log;
use Droath\NextusAi\Plugins\AgentWorkerPluginManager;
use Droath\NextusAi\Plugins\Contracts\AgentWorkerPluginInterface;

class NextusAiAgent
{
    public function run(
        string $pluginId,
        string|array $message,
    ): array {
        try {
            $manager = app(AgentWorkerPluginManager::class);
            $agent = $manager->createInstance($pluginId);

            if ($agent instanceof AgentWorkerPluginInterface) {
                return $agent->respond(
                    is_array($message) ? $message : [$message]
                );
            }
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
        }

        return [];
    }
}
