<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class PostResource extends JsonApiResource
{
    /**
     * The number of times the "comments" relationship closure has been resolved.
     */
    public static int $commentsResolutionCount = 0;

    protected array $attributes = [
        'title',
        'content',
    ];

    #[\Override]
    public function toRelationships(Request $request)
    {
        return [
            'author' => AuthorResource::class,
            'comments' => function () {
                static::$commentsResolutionCount++;

                return CommentResource::collection(
                    $this->comments->where('content', '!=', 'private')
                );
            },
        ];
    }
}
