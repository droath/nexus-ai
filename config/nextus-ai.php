<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'api.openai.com/v1'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],
    'perplexity' => [
        'api_key' => env('PERPLEXITY_API_KEY'),
    ],
    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL'),
    ],
    'managers' => [
        'agent' => [
            'namespaces' => ['App\Plugins'],
        ],
        'agent_tool' => [
            'namespaces' => ['App\Plugins'],
        ],
        'agent_coordinator' => [
            'namespaces' => ['App\Plugins'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the memory system for LLM agents. Agents can store and retrieve
    | key-value data using different strategies (session, database, etc.).
    | Memory instances are shared across agents in coordinator executions.
    |
    */
    'memory' => [
        /*
        |--------------------------------------------------------------------------
        | Default Memory Strategy
        |--------------------------------------------------------------------------
        |
        | This is the default memory strategy used when creating memory instances.
        | Supported: "session", "database", "null"
        |
        */
        'default' => env('NEXTUS_AI_MEMORY_DRIVER', 'session'),

        /*
        |--------------------------------------------------------------------------
        | Default TTL (Time To Live)
        |--------------------------------------------------------------------------
        |
        | Default time-to-live in seconds for memory entries when no TTL is specified.
        | Set to 0 for no expiration. Default is 1 hour (3600 seconds).
        |
        */
        'default_ttl' => (int) env('NEXTUS_AI_MEMORY_TTL', 3600),

        /*
        |--------------------------------------------------------------------------
        | Memory Strategy Configurations
        |--------------------------------------------------------------------------
        |
        | Configure each memory strategy with its specific settings.
        | Each strategy can be enabled/disabled and customized as needed.
        |
        */
        'strategies' => [
            /*
            |--------------------------------------------------------------------------
            | Session Memory Strategy
            |--------------------------------------------------------------------------
            |
            | Stores memory data in the Laravel session. Fast and request-scoped,
            | but data is lost when the session expires or application restarts.
            |
            */
            'session' => [
                'prefix' => env('NEXTUS_AI_MEMORY_SESSION_PREFIX', 'agent_memory'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Database Memory Strategy
            |--------------------------------------------------------------------------
            |
            | Stores memory data in the database. Persistent across requests and
            | application restarts, but requires database queries for operations.
            |
            */
            'database' => [
                'table' => env('NEXTUS_AI_MEMORY_DATABASE_TABLE', 'llm_agent_memory'),
                'connection' => env('NEXTUS_AI_MEMORY_DATABASE_CONNECTION'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Null Memory Strategy
            |--------------------------------------------------------------------------
            |
            | No-op memory strategy for testing or when memory is not needed.
            | All operations succeed but no data is actually stored or retrieved.
            |
            */
            'null' => [],
        ],

        /*
        |--------------------------------------------------------------------------
        | Memory Cleanup Configuration
        |--------------------------------------------------------------------------
        |
        | Configure automatic cleanup of expired memory entries. The cleanup
        | service will iterate through specified strategies and remove expired
        | entries to maintain performance and prevent storage bloat.
        |
        | To enable automatic scheduling, add the scheduler code to your
        | routes/console.php file. See SCHEDULER.md for complete setup instructions.
        |
        */
        'cleanup' => [
            /*
            |--------------------------------------------------------------------------
            | Cleanup Strategies
            |--------------------------------------------------------------------------
            |
            | Specify which memory strategies should be included in automatic cleanup.
            | Only strategies that support expiration (like database) need cleanup.
            |
            */
            'strategies' => ['database'],

            /*
            |--------------------------------------------------------------------------
            | Cleanup Schedule
            |--------------------------------------------------------------------------
            |
            | Configure when the cleanup should run. Uses Laravel scheduler syntax.
            | Examples: 'hourly', 'daily', 'twiceDaily', 'weekly'
            |
            */
            'schedule' => env('NEXTUS_AI_MEMORY_CLEANUP_SCHEDULE', 'daily'),

            /*
            |--------------------------------------------------------------------------
            | Cleanup Enabled
            |--------------------------------------------------------------------------
            |
            | Enable or disable automatic memory cleanup. Set to false to disable
            | the scheduled cleanup entirely.
            |
            */
            'enabled' => env('NEXTUS_AI_MEMORY_CLEANUP_ENABLED', true),
        ],
    ],
];
