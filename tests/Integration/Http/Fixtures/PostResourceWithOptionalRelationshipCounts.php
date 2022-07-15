<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithOptionalRelationshipCounts extends PostResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'authors' => $this->whenCounted('authors_count'),
            'comments' => $this->whenCounted('comments', function ($count) {
                return "$count comments";
            }, 'None'),
        ];
    }
}
