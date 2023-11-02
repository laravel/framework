<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithOptionalRelationship extends PostResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'comments' => new CommentCollection($this->whenLoaded('comments')),
            'author' => new AuthorResource($this->whenLoaded('author')),
            'author_name' => $this->whenLoaded('author', function () {
                return $this->author?->name;
            }),
            'category' => $this->whenLoaded('category', function () {
                return $this->category?->name ?? 'No Category';
            }),
        ];
    }
}
