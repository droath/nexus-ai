<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources\Contracts;

use Droath\NextusAi\Drivers\Contracts\DriverInterface;

interface HasDriverInterface
{
    /**
     * Get the driver instance.
     */
    public function driver(): DriverInterface;
}
