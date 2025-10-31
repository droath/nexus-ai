<?php

use Droath\NextusAi\Drivers\Openai;
use Droath\NextusAi\Plugins\AgentToolPluginManager;

test('can create a get_weather tool and transform using OpenAI driver', function () {
    $manager = new AgentToolPluginManager([
        'Droath\NextusAi\Tests\Fixtures\Plugins',
    ]);
    $instance = $manager->createInstance('get_weather');

    expect($instance)->not()->toBeNull();

    $toolDefinition = $instance->definition();
    expect($toolDefinition)->not()->toBeNull();

    $tool = Openai::transformTool($toolDefinition);

    expect($tool)->toHaveKey('type', 'function')
        ->and($tool)->toHaveKey('name', 'get_weather')
        ->and($tool)->toHaveKey('parameters');

    $parameters = $tool['parameters'];
    expect($parameters)->toHaveKey('type', 'object')
        ->and($parameters)->toHaveKey('properties')
        ->and($parameters)->toHaveKey('required');

    $properties = $parameters['properties'];
    expect($properties)->toHaveKey('location')
        ->and($properties['location'])->toHaveKey('type', 'string')
        ->and($parameters['required'])->toContain('location')
        ->and($properties)->toHaveKey('unit')
        ->and($properties['unit'])->toHaveKey('type', 'string')
        ->and($properties['unit'])->toHaveKey('description', 'The city and state, e.g. San Francisco, CA')
        ->and($properties['unit'])->toHaveKey('enum')
        ->and($properties['unit']['enum'])->toContain('celsius')
        ->and($properties['unit']['enum'])->toContain('fahrenheit');
});

test('get_weather tool executes and returns the expected response', function () {
    $manager = new AgentToolPluginManager([
        'Droath\NextusAi\Tests\Fixtures\Plugins',
    ]);

    $instance = $manager->createInstance('get_weather');

    $result = $instance->execute([
        'location' => 'Denver, CO',
        'unit' => 'fahrenheit',
    ]);

    expect($result)->toBe("It's 59 degrees fahrenheit in Denver, CO today!");
});
