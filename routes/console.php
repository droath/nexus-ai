<?php

use Droath\NextusAi\Console\Commands\MemoryCleanupCommand;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Nextus AI Console Routes
|--------------------------------------------------------------------------
|
| This file contains the console route definitions for the Nextus AI
| package. You can copy these schedules to your application's
| routes/console.php file or bootstrap/app.php file.
|
*/

/**
 * Schedule memory cleanup based on configuration.
 *
 * Copy this code to your application's routes/console.php file:
 */

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
        default => $command->daily(), // fallback to daily
    };

    // Add production safety options
    $command->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();
}
