<?php

namespace Droath\NextusAi\Tests;

use Droath\NextusAi\NextusAiServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        // Use array cache driver to avoid database cache table requirement
        config()->set('cache.default', 'array');

        /*
        $migration = include __DIR__.'/../database/migrations/create_nextus_ai_table.php.stub';
        $migration->up();
        */
    }

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Droath\\NextusAi\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
        // Load package migrations for testing
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            NextusAiServiceProvider::class,
        ];
    }
}
