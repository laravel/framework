<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithOptionalRelationshipExists extends PostResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'has_authors' => $this->whenExistsLoaded('authors'),
            'has_favourited_posts' => $this->whenExistsLoaded('favouritedPosts', fn ($exists) => $exists ? 'Yes' : 'No', 'No'),
            'comment_exists' => $this->whenExistsLoaded('comments'),
        ];
    }
}
