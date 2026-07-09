<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class PostResource extends JsonApiResource
{
    protected array $attributes = [
        'title',
        'content',
    ];

    protected array $relationships = [
        'author' => AuthorResource::class,
        'comments',
    ];
}
