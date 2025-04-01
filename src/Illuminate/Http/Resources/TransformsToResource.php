<?php

namespace Illuminate\Http\Resources;

use LogicException;
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
        throw_unless(
            class_exists($resourceClass = static::guessResourceName()),
            LogicException::class, sprintf('Failed to find resource class for model [%s].', get_class($this))
        );

        return $resourceClass::make($this);
    }

    /**
     * Guess the resource class name for the model.
     *
     * @return class-string<JsonResource>
     */
    public static function guessResourceName(): string
    {
        $modelClass = static::class;

        $modelNamespace = str_replace('App\\Models\\', '', $modelClass);
        $modelNamespace = str_replace('\\'.class_basename($modelClass), '', $modelNamespace);

        return sprintf(
            'App\\Http\\Resources\\%s%sResource',
            $modelNamespace ? $modelNamespace.'\\' : '',
            class_basename($modelClass)
        );
    }
}
