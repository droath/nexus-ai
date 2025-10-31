# Nextus AI - Laravel LLM Provider Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/droath/nextus-ai.svg?style=flat-square)](https://packagist.org/packages/droath/nextus-ai)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/droath/nextus-ai/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/droath/nextus-ai/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/droath/nextus-ai/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/droath/nextus-ai/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/droath/nextus-ai.svg?style=flat-square)](https://packagist.org/packages/droath/nextus-ai)

Nextus AI is a unified Laravel client library designed for multiple LLM
providers, including OpenAI, Anthropic Claude, and Perplexity. This package
offers a consistent interface for working with Large Language Models, featuring
agent coordination, tool execution, and flexible memory strategies.

## Features

- **Multi-Provider Support**: Seamlessly switch between OpenAI, Anthropic
  Claude, and Perplexity with a unified API
- **Driver Pattern Architecture**: Clean abstraction layer for LLM provider
  integrations
- **Agent System**: Coordinate multiple AI agents with parallel, sequential, or
  router strategies
- **Tool Integration**: Define and execute tools that LLMs can call during
  conversations
- **Memory Strategies**: Flexible memory management with database, session, or
  null strategies
- **Streaming Support**: Real-time streaming responses from compatible providers
- **Testing Helpers**: Built-in fake clients and testing utilities
- **Laravel Integration**: Native Laravel service provider with auto-discovery

## Requirements

- PHP 8.3 or higher
- Laravel 11.x or higher
- Valid API keys for desired LLM providers

## Installation

Install the package via Composer:

```bash
composer require droath/nextus-ai
```

The service provider will be automatically discovered by Laravel.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="nextus-ai-config"
```

This will create `config/nextus-ai.php` where you can configure your LLM
providers.

### Environment Variables

Add your API keys to your `.env` file:

```env
# OpenAI Configuration
OPENAI_API_KEY=your-openai-api-key
OPENAI_ORGANIZATION=your-org-id  # Optional

# Anthropic Claude Configuration
CLAUDE_API_KEY=your-claude-api-key

# Perplexity Configuration
PERPLEXITY_API_KEY=your-perplexity-api-key
```

### Database Migrations

If you plan to use the database memory strategy or agent memory features, run
the migrations:

```bash
php artisan migrate
```

## Usage

### Basic Chat Example

```php
use Droath\NextusAi\Facades\NextusAi;
use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Messages\UserMessage;

// Using OpenAI with Message objects (recommended)
$response = NextusAi::driver(LlmProvider::OPENAI)
    ->chat()
    ->withMessages([
        UserMessage::make('What is Laravel?')
    ])
    ->send();

echo $response->getMessage();

// Alternative: Using array syntax (also supported)
$response = NextusAi::driver(LlmProvider::OPENAI)
    ->chat()
    ->withMessages([
        ['role' => 'user', 'content' => 'What is Laravel?']
    ])
    ->send();
```

### Using Different Providers

```php
use Droath\NextusAi\Messages\UserMessage;
use Droath\NextusAi\Messages\SystemMessage;

// Using Claude with Message objects
$claudeResponse = NextusAi::driver(LlmProvider::CLAUDE)
    ->chat()
    ->withModel('claude-3-5-sonnet-20241022')
    ->withMessages([
        SystemMessage::make('You are a helpful programming assistant.'),
        UserMessage::make('Explain async programming')
    ])
    ->send();

// Using Perplexity
$perplexityResponse = NextusAi::driver(LlmProvider::PERPLEXITY)
    ->chat()
    ->withMessages([
        UserMessage::make('Latest news on AI developments')
    ])
    ->send();
```

### Message Classes

The package provides dedicated message classes for better type safety and
structure:

```php
use Droath\NextusAi\Messages\UserMessage;
use Droath\NextusAi\Messages\SystemMessage;
use Droath\NextusAi\Messages\AssistantMessage;

// Create messages using the make() method
$systemMessage = SystemMessage::make('You are a helpful assistant.');
$userMessage = UserMessage::make('Hello, how are you?');

// Send conversation with multiple messages
$response = NextusAi::driver('openai')
    ->chat()
    ->withMessages([
        SystemMessage::make('You are a helpful assistant specialized in Laravel.'),
        UserMessage::make('What are the new features in Laravel 11?'),
    ])
    ->send();

// UserMessage supports context for additional metadata
$messageWithContext = UserMessage::make(
    'Analyze this code',
    context: 'This is a Laravel controller with CRUD operations'
);
```

**Available Message Classes:**

- `UserMessage` - Messages from the user
- `SystemMessage` - System instructions/prompts
- `AssistantMessage` - Messages from the AI assistant

### Working with Agents

Create intelligent agents that can coordinate tasks:

```php
use Droath\NextusAi\Agents\Agent;
use Droath\NextusAi\Agents\AgentCoordinator;
use Droath\NextusAi\Agents\Enums\AgentStrategy;

// Create specialized agents
$researchAgent = Agent::make()
    ->setSystemPrompt('You are a research assistant specialized in gathering information.');

$writerAgent = Agent::make()
    ->setSystemPrompt('You are a content writer who creates engaging articles.');

// Coordinate agents with a strategy
$coordinator = AgentCoordinator::make(
    'Create a blog post about Laravel',
    [$researchAgent, $writerAgent],
    AgentStrategy::SEQUENTIAL
);

$result = $coordinator->execute($resource);
```

### Using Tools

Define tools that LLMs can invoke:

```php
use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Tools\ToolProperty;

$weatherTool = Tool::make('get_weather')
    ->describe('Get the current weather for a location')
    ->using(function (array $arguments) {
        // Your tool implementation here
        $location = $arguments['location'];
        $unit = $arguments['unit'] ?? 'fahrenheit';

        // Example: fetch weather from an API
        return "The current weather in {$location} is 72 degrees {$unit}.";
    })
    ->withProperties([
        ToolProperty::make('location', 'string')
            ->describe('The city and state, e.g. San Francisco, CA')
            ->required(),
        ToolProperty::make('unit', 'string')
            ->describe('Temperature unit: celsius or fahrenheit')
            ->withEnums(['celsius', 'fahrenheit']),
    ]);

$response = NextusAi::driver('openai')
    ->chat()
    ->withTools([$weatherTool])
    ->withMessages([
        UserMessage::make('What is the weather in San Francisco?')
    ])
    ->send();

// If the LLM decides to use the tool, you can access the tool calls
// and execute them to get results
```

### Memory Strategies

Use memory to maintain context across interactions:

```php
use Droath\NextusAi\Memory\MemoryDefinition;
use Droath\NextusAi\Memory\MemoryStrategyFactory;
use Droath\NextusAi\Agents\Agent;

// Database memory strategy
$memoryDefinition = new MemoryDefinition('database', [
    'connection' => 'mysql',
    'table' => 'llm_agent_memory'
]);

$factory = new MemoryStrategyFactory($memoryDefinition);
$memory = $factory->createInstance();

$agent = Agent::make()
    ->setSystemPrompt('You remember previous conversations')
    ->setMemory($memory);

// Store information
$memory->set('user_preference', 'prefers concise answers');

// Retrieve later
$preference = $memory->get('user_preference');
```

### Streaming Responses

Get real-time streaming responses:

```php
use Droath\NextusAi\Messages\UserMessage;

$stream = NextusAi::driver('openai')
    ->chat()
    ->withModel('gpt-4')
    ->withMessages([
        UserMessage::make('Write a long story')
    ])
    ->stream();

foreach ($stream as $chunk) {
    echo $chunk;
}
```

## Supported LLM Providers

### OpenAI

- Models: GPT-4, GPT-4 Turbo, GPT-3.5 Turbo, and more
- Features: Chat, embeddings, function calling, streaming
- Configuration: API key, organization ID (optional), base URL (optional)

### Anthropic Claude

- Models: Claude 3.5 Sonnet, Claude 3 Opus, Claude 3 Haiku
- Features: Chat, function calling, streaming
- Configuration: API key

### Perplexity

- Models: Various Perplexity models
- Features: Chat with web search capabilities
- Configuration: API key

## Advanced Features

### Agent Coordination Strategies

1. **Parallel**: Execute multiple agents simultaneously
2. **Sequential**: Execute agents one after another, passing results
3. **Router**: Intelligently route requests to the most appropriate agent

### Memory Strategies

- **Database Strategy**: Persistent storage using Laravel's database
- **Session Strategy**: Session-based temporary storage
- **Null Strategy**: No memory persistence (stateless)

### Custom Drivers

Extend the package to support additional LLM providers by implementing the
driver interface:

```php
use Droath\NextusAi\Drivers\NextusAiDriver;

class CustomDriver extends NextusAiDriver
{
    // Implement required methods
}
```

## Testing

Run the test suite:

```bash
composer test
```

Run code style checks:

```bash
vendor/bin/pint
```

Run static analysis:

```bash
vendor/bin/phpstan analyse
```

### Testing Your Application

Use the built-in fake client for testing:

```php
use Droath\NextusAi\Facades\NextusAi;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

NextusAi::fake(
    responseCallback: fn() => NextusAiResponseMessage::fromString('Fake response')
);

// Your test code here
```

## Console Commands

### Memory Cleanup

Clean up expired memory entries:

```bash
# Perform cleanup
php artisan nextus-ai:memory:cleanup

# Dry run to see what would be cleaned
php artisan nextus-ai:memory:cleanup --dry-run
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Travis Tomka](https://github.com/droath)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more
information.
