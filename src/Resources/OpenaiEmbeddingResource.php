<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources;

use Droath\NextusAi\Drivers\Openai;
use OpenAI\Responses\Embeddings\CreateResponse;
use Droath\NextusAi\Resources\Concerns\WithInput;
use Droath\NextusAi\Resources\Concerns\WithModel;
use OpenAI\Contracts\Resources\EmbeddingsContract;
use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use Droath\NextusAi\Responses\NextusAiResponseEmbeddings;
use Droath\NextusAi\Resources\Contracts\HasInputInterface;
use Droath\NextusAi\Resources\Contracts\HasDriverInterface;
use Droath\NextusAi\Resources\Contracts\EmbeddingsResourceInterface;

class OpenaiEmbeddingResource extends ResourceBase implements EmbeddingsResourceInterface, HasDriverInterface, HasInputInterface
{
    use WithInput;
    use WithModel;

    protected string $model = Openai::DEFAULT_EMBEDDING_MODEL;

    public function __construct(
        protected EmbeddingsContract $resource,
        protected DriverInterface $driver
    ) {}

    public function __invoke(): NextusAiResponseEmbeddings
    {
        return $this->handleResponse(
            $this->resource->create($this->resourceParameters())
        );
    }

    /**
     * {@inheritDoc}
     */
    public function driver(): DriverInterface
    {
        return $this->driver;
    }

    protected function handleResponse(CreateResponse $response): NextusAiResponseEmbeddings
    {
        return NextusAiResponseEmbeddings::fromArray(
            $response->embeddings[0]->embedding
        );
    }

    protected function resourceParameters(): array
    {
        return array_filter([
            'model' => $this->model,
            'input' => $this->input,
        ]);
    }
}
