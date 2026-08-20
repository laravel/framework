<?php

namespace Illuminate\Tests\App\Http\Resources\JsonApi;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class CommentResource extends JsonApiResource
{
    /**
     * The resource's attributes.
     */
    public $attributes = [
        'content',
    ];

    /**
     * The resource's relationships.
     */
    public $relationships = [
        'posts',
        'commenter' => UserResource::class,
    ];
}
