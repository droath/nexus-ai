<?php

declare(strict_types=1);

namespace Droath\NextusAi\Agents\Enums;

enum AgentStrategy: string
{
    case ROUTER = 'router';
    case PARALLEL = 'parallel';
    case SEQUENTIAL = 'sequential';
}
