<?php

namespace Illuminate\Tests\App\Http\Resources;

class AuthorResourceWithOptionalRelationship extends PostResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'posts_count' => $this->whenLoaded('posts', function () {
                return $this->posts->count().' posts';
            }, function () {
                return 'not loaded';
            }),
            'latest_post_title' => $this->whenLoaded('posts', function () {
                return $this->posts->first()?->title ?: 'no posts yet';
            }, 'not loaded'),
        ];
    }
}
