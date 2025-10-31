<?php

declare(strict_types=1);

namespace Droath\NextusAi\Testing;

use Droath\NextusAi\Drivers\Enums\LlmProvider;
use Droath\NextusAi\Resources\Contracts\ChatResourceInterface;
use Droath\NextusAi\Resources\Contracts\ResourceInterface;
use Droath\NextusAi\Resources\Contracts\ResponsesResourceInterface;
use Droath\NextusAi\Testing\Resources\FakeResource;
use Illuminate\Support\Testing\Fakes\Fake;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

use function PHPUnit\Framework\assertEquals;

class NextusAiFake implements Fake
{
    protected ?LlmProvider $provider = null;

    protected ?ResourceInterface $resource = null;

    public function __construct(
        protected ?\Closure $responseCallback = null,
        protected ?\Closure $resourceCallback = null,
    ) {}

    /**
     * Create a fake chat resource.
     */
    public function chat(LlmProvider $provider): ChatResourceInterface
    {
        $this->provider = $provider;

        $this->resource = $this->buildResource();

        return $this->resource;
    }

    /**
     * Create a fake responses resource.
     */
    public function responses(LlmProvider $provider): ResponsesResourceInterface
    {
        $this->provider = $provider;

        $this->resource = $this->buildResource();

        return $this->resource;
    }

    /**
     * Assert the resource is as expected.
     */
    public function assertResource(?\Closure $expected = null): void
    {
        if ($this->resource === null) {
            throw new AssertionFailedError(
                'No resource was created. Make sure to invoke the resource method
                before asserting the resource.'
            );
        }

        if (is_callable($expected)) {
            $expected($this->resource);
        } else {
            Assert::assertEquals($expected, $this->resource);
        }
    }

    /**
     * Assert the resource provider is as expected.
     */
    public function assertProvider(\Closure|LlmProvider $expected): void
    {
        if ($this->provider === null) {
            throw new AssertionFailedError(
                'No resource was created. Make sure to invoke the resource method
                before asserting the provider.'
            );
        }

        if (is_callable($expected)) {
            $expected($this->provider);
        } else {
            assertEquals($expected, $this->provider);
        }
    }

    protected function buildResource(): ResourceInterface
    {
        return is_callable($this->resourceCallback)
            ? call_user_func($this->resourceCallback, $this->provider)
            : new FakeResource($this->provider, $this->responseCallback);
    }
}
