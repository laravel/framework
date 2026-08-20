<?php

namespace Illuminate\Tests\App\Http\Resources;

class PostResourceWithOptionalRelationship extends PostResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'comments' => new CommentCollection($this->whenLoaded('comments')),
            'author' => new AuthorResource($this->whenLoaded('author')),
            'author_name' => $this->whenLoaded('author', function () {
                return $this->author->name;
            }),
        ];
    }
}
