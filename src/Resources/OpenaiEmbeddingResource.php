<?php

declare(strict_types=1);

namespace Droath\NextusAi\Resources;

use OpenAI\Contracts\Resources\EmbeddingsContract;
use Droath\NextusAi\Drivers\Contracts\DriverInterface;
use Droath\NextusAi\Drivers\Openai;
use Droath\NextusAi\Resources\Concerns\WithInput;
use Droath\NextusAi\Resources\Concerns\WithModel;
use Droath\NextusAi\Resources\Contracts\EmbeddingsResourceInterface;
use Droath\NextusAi\Resources\Contracts\HasDriverInterface;
use Droath\NextusAi\Resources\Contracts\HasInputInterface;
use Droath\NextusAi\Responses\NextusAiResponseEmbeddings;
use OpenAI\Responses\Embeddings\CreateResponse;

class OpenaiEmbeddingResource extends ResourceBase implements EmbeddingsResourceInterface, HasDriverInterface, HasInputInterface
{
    protected string $model = Openai::DEFAULT_EMBEDDING_MODEL;

    use WithInput;
    use WithModel;

    public function __construct(
        protected EmbeddingsContract $resource,
        protected DriverInterface $driver
    ) {}

    /**
     * {@inheritDoc}
     */
    public function driver(): DriverInterface
    {
        return $this->driver;
    }

    public function __invoke(): NextusAiResponseEmbeddings
    {
        return $this->handleResponse(
            $this->resource->create($this->resourceParameters())
        );
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
