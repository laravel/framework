<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\LazyCollection;

class TeamWithLazyAttributeResource extends JsonApiResource
{
    public function toType(Request $request)
    {
        return 'teams';
    }

    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
            'tags' => LazyCollection::make(fn () => yield 'php'),
        ];
    }
}
