<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class AuthorResource extends JsonApiResource
{
    protected array $relationships = [
        'comments',
        'profile',
        'chaperonePosts' => PostResource::class,
    ];

    #[\Override]
    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
