<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithOptionalRelationshipUsingNamedParameters extends PostResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'author' => new AuthorResource($this->whenLoaded('author')),
            'author_defaulting_to_null' => new AuthorResource($this->whenLoaded('author', default: null)),
            'author_name' => $this->whenLoaded('author', fn ($author) => $author->name, 'Anonymous'),
        ];
    }
}
