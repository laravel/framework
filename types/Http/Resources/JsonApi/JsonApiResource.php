<?php

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

use function PHPStan\Testing\assertType;

class PostResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [];
    }
}

assertType('Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection', JsonApiResource::collection([]));
assertType('Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection', PostResource::collection([]));
assertType('Illuminate\Http\Resources\Json\AnonymousResourceCollection', JsonResource::collection([]));
assertType('array<string, mixed>', (new PostResource(null))->toAttributes(new Request));
