<?php

namespace Illuminate\Tests\App\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ApiPostResource extends JsonApiResource
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
    public function toType(Request $request)
    {
        return 'posts';
    }

    #[\Override]
    public function toRelationships(Request $request)
    {
        return [
            'author' => ApiAuthorResource::class,
            'comments' => function () {
                static::$commentsResolutionCount++;

                return CommentResource::collection(
                    $this->comments->where('content', '!=', 'private')
                );
            },
        ];
    }
}
