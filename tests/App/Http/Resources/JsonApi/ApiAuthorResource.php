<?php

namespace Illuminate\Tests\App\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ApiAuthorResource extends JsonApiResource
{
    protected array $relationships = [
        'comments',
        'profile',
        'chaperonePosts' => ApiPostResource::class,
    ];

    #[\Override]
    public function toType(Request $request)
    {
        return 'authors';
    }

    #[\Override]
    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
