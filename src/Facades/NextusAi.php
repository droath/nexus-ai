<?php

namespace Droath\NextusAi\Facades;

use Illuminate\Support\Facades\Facade;
use Droath\NextusAi\Responses\NextusAiResponseMessage;

/**
 * @mixin \Droath\NextusAi\NextusAi
 * @mixin \Droath\NextusAi\Testing\NextusAiFake
 *
 * @see \Droath\NextusAi\NextusAi
 */
class NextusAi extends Facade
{
    public static function fake(
        ?\Closure $responseCallback = null,
        ?\Closure $resourceCallback = null
    ) {
        if (is_null($responseCallback)) {
            $responseCallback = function () {
                return NextusAiResponseMessage::fromString(
                    'This is a fake response.'
                );
            };
        }

        return tap(
            static::getFacadeRoot(),
            function ($fake) use ($responseCallback, $resourceCallback) {
                static::swap($fake->fake($responseCallback, $resourceCallback));
            }
        );
    }

    protected static function getFacadeAccessor(): string
    {
        return \Droath\NextusAi\NextusAi::class;
    }
}
