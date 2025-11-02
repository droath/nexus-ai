<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources;

use Droath\NextusAi\Resources\Contracts\ResourceInterface;

/**
 * Base class for all resources.
 *
 * Provides common functionality shared across all resource implementations.
 */
abstract class ResourceBase implements ResourceInterface
{
    /**
     * {@inheritDoc}
     */
    public function call(): mixed
    {
        return $this->__invoke();
    }
}
