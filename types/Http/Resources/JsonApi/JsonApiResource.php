<?php

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

use function PHPStan\Testing\assertType;

assertType(
    'Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection',
    JsonApiResource::collection([]),
);
