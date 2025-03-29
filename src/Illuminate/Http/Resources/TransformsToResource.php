<?php

namespace Illuminate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

trait TransformsToResource
{
    /**
     * Create a new resource object for the given resource.
     *
     * @param  class-string<JsonResource>|null  $resourceClass
     * @return JsonResource
     *
     * @throws \Throwable
     */
    public function toResource(?string $resourceClass = null): JsonResource
    {
        if ($resourceClass === null) {
            return $this->guessResource();
        }

        return $resourceClass::make($this);
    }

    /**
     * Guess the resource class for the model.
     *
     * @return JsonResource
     *
     * @throws \Throwable
     */
    protected function guessResource(): JsonResource
    {
        $className = get_class($this);

        $resourceClass = static::guessResourceName();

        throw_unless(class_exists($resourceClass), \LogicException::class, sprintf('Failed to find resource class for model [%s].', $className));

        return $resourceClass::make($this);
    }

    /**
     * Guess the resource class name for the model.
     *
     * @return class-string<JsonResource>
     */
    public static function guessResourceName(): string
    {
        return sprintf('App\Http\Resources\%sResource', class_basename(static::class));
    }
}
