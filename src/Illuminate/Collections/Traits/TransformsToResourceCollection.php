<?php

namespace Illuminate\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use LogicException;

trait TransformsToResourceCollection
{
    /**
     * Create a new resource collection instance for the given resource.
     *
     * @param  class-string<JsonResource>|null  $resourceClass
     * @return ResourceCollection
     *
     * @throws \Throwable
     */
    public function toResourceCollection(?string $resourceClass = null): ResourceCollection
    {
        if ($resourceClass === null) {
            return $this->guessResourceCollection();
        }

        return $resourceClass::collection($this);
    }

    /**
     * Guess the resource collection for the items.
     *
     * @return ResourceCollection
     *
     * @throws \Throwable
     */
    protected function guessResourceCollection(): ResourceCollection
    {
        if ($this->isEmpty()) {
            return new ResourceCollection($this);
        }

        $model = $this->items[0] ?? null;

        throw_unless(is_object($model), LogicException::class, 'Resource collection guesser expects the collection to contain objects.');

        /** @var class-string<Model> $className */
        $className = get_class($model);

        throw_unless(method_exists($className, 'guessResourceName'), LogicException::class, sprintf('Expected class %s to implement guessResourceName method. Make sure the model uses the TransformsToResource trait.', $className));

        foreach ($className::guessResourceName() as $resourceClass) {
            if (is_string($resourceClass) && class_exists($resourceClass)) {
                return $resourceClass::collection($this);
            }
        }

        throw new LogicException(sprintf('Failed to find resource class for model [%s].', $className));
    }
}
