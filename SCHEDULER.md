# Nextus AI - Memory Cleanup Scheduler

The Nextus AI package includes an automatic memory cleanup system that removes expired memory entries to maintain performance and prevent storage bloat.

## Configuration

The memory cleanup system is configured in your `config/nextus-ai.php` file:

```php
'memory' => [
    'cleanup' => [
        // Which memory strategies should be cleaned up
        'strategies' => ['database'],

        // When the cleanup should run (Laravel scheduler syntax)
        'schedule' => env('NEXTUS_AI_MEMORY_CLEANUP_SCHEDULE', 'daily'),

        // Enable or disable automatic cleanup
        'enabled' => env('NEXTUS_AI_MEMORY_CLEANUP_ENABLED', true),
    ],
],
```

## Environment Variables

You can control the scheduler via environment variables:

```bash
# Enable/disable the memory cleanup
NEXTUS_AI_MEMORY_CLEANUP_ENABLED=true

# Set the cleanup frequency
NEXTUS_AI_MEMORY_CLEANUP_SCHEDULE=daily
```

### Available Schedule Frequencies

- `hourly` - Run every hour
- `daily` - Run once per day (default)
- `twiceDaily` - Run twice per day
- `weekly` - Run once per week
- `monthly` - Run once per month

## Setting Up the Scheduler

### Option 1: Using `routes/console.php` (Recommended)

Add the following to your application's `routes/console.php` file:

```php
<?php

use Droath\NextusAi\Console\Commands\MemoryCleanupCommand;
use Illuminate\Support\Facades\Schedule;

// Memory cleanup scheduling - only if enabled in config
if (config('nextus-ai.memory.cleanup.enabled', true)) {
    $scheduleFrequency = config('nextus-ai.memory.cleanup.schedule', 'daily');

    $command = Schedule::command(MemoryCleanupCommand::class);

    // Apply the configured schedule frequency
    match ($scheduleFrequency) {
        'hourly' => $command->hourly(),
        'daily' => $command->daily(),
        'twiceDaily' => $command->twiceDaily(),
        'weekly' => $command->weekly(),
        'monthly' => $command->monthly(),
        default => $command->daily(),
    };

    // Production safety options
    $command->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();
}
```

### Option 2: Using `bootstrap/app.php`

If you prefer to keep your `routes/console.php` file clean, you can add the scheduler in your `bootstrap/app.php` file:

```php
<?php

use Droath\NextusAi\Console\Commands\MemoryCleanupCommand;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    // ... other configuration
    ->withSchedule(function (Schedule $schedule) {
        // Memory cleanup scheduling - only if enabled in config
        if (config('nextus-ai.memory.cleanup.enabled', true)) {
            $scheduleFrequency = config('nextus-ai.memory.cleanup.schedule', 'daily');

            $command = $schedule->command(MemoryCleanupCommand::class);

            // Apply the configured schedule frequency
            match ($scheduleFrequency) {
                'hourly' => $command->hourly(),
                'daily' => $command->daily(),
                'twiceDaily' => $command->twiceDaily(),
                'weekly' => $command->weekly(),
                'monthly' => $command->monthly(),
                default => $command->daily(),
            };

            // Production safety options
            $command->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();
        }
    });
```

## Manual Execution

You can also run the memory cleanup manually at any time:

```bash
# Run cleanup
php artisan nextus-ai:memory:cleanup

# Run in dry-run mode to see what would be cleaned
php artisan nextus-ai:memory:cleanup --dry-run
```

## Server Cron Setup

Don't forget to add Laravel's scheduler to your server's cron:

```bash
# Edit crontab
crontab -e

# Add this line (adjust the path to your project)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Monitoring

You can view your scheduled tasks and their next run times:

```bash
php artisan schedule:list
```

The memory cleanup command includes built-in safety features:
- **`withoutOverlapping()`** - Prevents multiple cleanup processes from running simultaneously
- **`onOneServer()`** - Ensures cleanup runs on only one server in multi-server setups
- **`runInBackground()`** - Prevents the cleanup from blocking other scheduled tasks
