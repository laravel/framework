<?php

namespace Illuminate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use LogicException;

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
        $resourceClass = static::guessResourceName();

        throw_unless(
            is_string($resourceClass) && class_exists($resourceClass),
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

        if (! Str::contains($modelClass, '\\Models\\')) {
            return false;
        }

        // Get everything after the "Models" namespace...
        $relativeNamespace = Str::after($modelClass, '\\Models\\');
        $relativeNamespace = str_replace('\\'.class_basename($modelClass), '', $relativeNamespace);

        if ($relativeNamespace === class_basename($modelClass)) {
            $relativeNamespace = '';
        }

        // Get the root namespace (everything before "Models")...
        $rootNamespace = Str::before($modelClass, '\\Models');

        return sprintf(
            '%s\\Http\\Resources\\%s%sResource',
            $rootNamespace,
            $relativeNamespace ? $relativeNamespace.'\\' : '',
            class_basename($modelClass)
        );
    }
}
