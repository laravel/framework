<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class PostApiResource extends JsonApiResource
{
    protected array $attributes = [
        'title',
        'content',
    ];
}
