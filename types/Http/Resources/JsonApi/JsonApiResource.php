<?php

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

use function PHPStan\Testing\assertType;

class PostResource extends JsonApiResource
{
}

assertType('Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection', JsonApiResource::collection([]));
assertType('Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection', PostResource::collection([]));
assertType('Illuminate\Http\Resources\Json\AnonymousResourceCollection', JsonResource::collection([]));
