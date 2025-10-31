<?php

use Droath\NextusAi\Tools\Tool;
use Droath\NextusAi\Tools\ToolProperty;

test('it creates a weather tool function with properties', function () {
    $tool = Tool::make('get_weather')
        ->describe('Get the current weather in a given location')
        ->using(function () {
            return "It's 89 degrees";
        })
        ->withProperties([
            ToolProperty::make('location', 'string')
                ->describe('The city and state, e.g. San Francisco, CA')
                ->required(),
            ToolProperty::make('unit', 'string')
                ->describe('The unit of measurement to return. Can be "imperial" or "metric".')
                ->withEnums(['celsius', 'fahrenheit']),
        ]);
    $data = $tool->toArray();

    expect($tool)
        ->toBeInstanceOf(Tool::class)
        ->and($data['name'])
        ->toEqual('get_weather')
        ->and($data['description'])
        ->toEqual('Get the current weather in a given location')
        ->and($data['properties'])
        ->toBeCollection()
        ->toHaveCount(2)
        ->and($data['required'])->toBeArray()->toMatchArray(['location'])
        ->and($tool())->toEqual("It's 89 degrees");
});
