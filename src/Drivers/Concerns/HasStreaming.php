<?php

declare(strict_types=1);

namespace Droath\NextusAi\Drivers\Concerns;

trait HasStreaming
{
    protected bool $stream = false;

    protected bool $useStreamBuffer = false;

    protected ?string $streamBuffer = null;

    protected \Closure $streamProcess;

    protected \Closure $streamBufferProcess;

    protected ?\Closure $streamFinished = null;

    /**
     * @return $this
     */
    public function usingStream(
        \Closure $streamProcess,
        ?\Closure $streamFinished = null
    ): static {
        $this->stream = true;
        $this->streamProcess = $streamProcess;
        $this->streamFinished = $streamFinished;

        return $this;
    }

    /**
     * @return $this
     */
    public function usingStreamBuffer(
        \Closure $streamProcess,
        \Closure $streamBufferProcess,
        ?\Closure $streamFinished = null
    ): static {
        $this->stream = true;
        $this->useStreamBuffer = true;

        $this->streamProcess = $streamProcess;
        $this->streamFinished = $streamFinished;
        $this->streamBufferProcess = $streamBufferProcess;

        return $this;
    }
}
