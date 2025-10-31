<?php

declare(strict_types=1);

namespace Droath\NextusAi\Facades;

use Illuminate\Support\Facades\Facade;

class NextusAiClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Droath\NextusAi\NextusAiClient::class;
    }
}
