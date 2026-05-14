<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class PostWithLazyLoadedCommentsResource extends JsonApiResource
{
    protected array $attributes = [
        'title',
        'content',
    ];

    public function toType(Request $request)
    {
        return 'posts';
    }

    public function toRelationships(Request $request): array
    {
        return [
            'comments' => fn () => tap(VisibleCommentResource::collection($this->comments), function ($collection) {
                $collection->collection->each->withType('public_comments');
            }),
        ];
    }
}
