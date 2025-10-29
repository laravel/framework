<?php

namespace Illuminate\Http\Resources\Json\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        return Arr::only(parent::toArray($request), $this->fields($request));
    }

    /**
     * Resolves `id` for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|int
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceIdentifier(Request $request): string|int
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
     * Resolves `meta` object for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    protected function resolveMetaInformations(Request $request): array
    {
        return array_merge($this->meta($request), $this->meta);
    }
}
