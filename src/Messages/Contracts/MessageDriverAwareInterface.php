<?php

declare(strict_types=1);

namespace Droath\NextusAi\Messages\Contracts;

use Droath\NextusAi\Drivers\Contracts\DriverInterface;

interface MessageDriverAwareInterface
{
    public function setDriver(DriverInterface $driver): void;
}
