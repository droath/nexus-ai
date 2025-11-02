<?php

declare(strict_types=1);

namespace Droath\NextusAi;

use Spatie\LaravelPackageTools\Package;
use Droath\NextusAi\Services\MemoryManager;
use Droath\NextusAi\Services\MemoryCleanupService;
use Droath\NextusAi\Plugins\AgentWorkerPluginManager;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Droath\NextusAi\Console\Commands\MemoryCleanupCommand;

class NextusAiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('nextus-ai')
            ->hasConfigFile()
            ->hasTranslations()
            ->discoversMigrations()
            ->hasCommand(MemoryCleanupCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(NextusAi::class, static function () {
            return new NextusAi();
        });

        $this->app->singleton(AgentWorkerPluginManager::class, function () {
            return new AgentWorkerPluginManager();
        });

        $this->app->singleton(MemoryManager::class, function () {
            return new MemoryManager();
        });

        $this->app->singleton(MemoryCleanupService::class, function () {
            return new MemoryCleanupService();
        });
    }
}
