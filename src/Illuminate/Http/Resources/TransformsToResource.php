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
        foreach (static::guessResourceName() as $resourceClass) {
            if (is_string($resourceClass) && class_exists($resourceClass)) {
                return $resourceClass::make($this);
            }
        }

        throw new LogicException(sprintf('Failed to find resource class for model [%s].', get_class($this)));
    }

    /**
     * Guess the resource class name for the model.
     *
     * @return array<class-string<JsonResource>>
     */
    public static function guessResourceName(): array
    {
        $modelClass = static::class;

        if (! Str::contains($modelClass, '\\Models\\')) {
            return [];
        }

        $relativeNamespace = Str::after($modelClass, '\\Models\\');

        $relativeNamespace = Str::contains($relativeNamespace, '\\')
            ? Str::before($relativeNamespace, '\\'.class_basename($modelClass))
            : '';

        $potentialResource = sprintf(
            '%s\\Http\\Resources\\%s%s',
            Str::before($modelClass, '\\Models'),
            strlen($relativeNamespace) > 0 ? $relativeNamespace.'\\' : '',
            class_basename($modelClass)
        );

        return [$potentialResource.'Resource', $potentialResource];
    }
}
