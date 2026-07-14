<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class PostResource extends JsonApiResource
{
    protected array $attributes = [
        'title',
        'content',
    ];

    #[\Override]
    public function toRelationships(Request $request)
    {
        return [
            'author' => AuthorResource::class,
            'comments' => fn () => CommentResource::collection(
                $this->comments->where('content', '!=', 'private')
            ),
        ];
    }
}
