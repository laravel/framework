<?php

namespace Illuminate\Http\Resources\Json\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

trait ResolvesJsonApiSpecifications
{
    /**
     * Resolves `attributes` for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|int
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceAttributes(Request $request): array
    {
        $data = (new Collection($this->toArray($request)))
            ->mapWithKeys(
                fn ($value, $key) => is_int($key) ? [$value => $this->resource[$value]] : [$key => $value]
            )->transform(fn ($value) => value($value, $request))
            ->all();

        return $this->filter($data);
    }

    /**
     * Resolves `id` for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|int
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceIdentifier(Request $request): string
    {
        if ($this->resource instanceof Model) {
            return $this->resource->getKey();
        }

        throw new RuntimeException('Unable to determine "type"');
    }

    /**
     * Resolves `type` for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceType(Request $request): string
    {
        if ($this->resource instanceof Model) {
            return Str::of(class_basename($this->resource))->snake()->pluralStudly();
        }

        throw new RuntimeException('Unable to determine "type"');
    }

    /**
     * Get unique key for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array{0: string, 1: string}
     */
    public function uniqueResourceKey(Request $request): array
    {
        return [$this->resolveResourceType($request), $this->resolveResourceIdentifier($request)];
    }

    /**
     * Resolves `meta` object for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    protected function resolveMetaInformations(Request $request): array
    {
        return array_merge($this->meta($request), $this->with);
    }
}
