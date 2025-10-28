<?php

namespace Illuminate\Http\Resources\JsonApi\Concerns;

use Illuminate\Http\Request;

trait InteractsWithMetaInformations
{
    /**
     * Resolves `meta` object for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function resolveMetaInformations(Request $request): array
    {
        return array_merge($this->meta($request), $this->meta);
    }
}
