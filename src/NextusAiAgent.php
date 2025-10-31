<?php

declare(strict_types=1);

namespace Droath\NextusAi;

use Droath\NextusAi\Plugins\AgentWorkerPluginManager;
use Illuminate\Support\Facades\Log;

class NextusAiAgent
{
    /**
     * @return mixed
     */
    public function run(
        string $pluginId,
        string|array $message,
    ): array {
        try {
            $manager = app(AgentWorkerPluginManager::class);
            /** @var \Droath\NextusAi\Plugins\Contracts\AgentWorkerPluginInterface $agent */
            if ($agent = $manager->createInstance($pluginId)) {
                return $agent->respond(
                    is_array($message) ? $message : [$message]
                );
            }
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage());
        }

        return [];
    }
}
