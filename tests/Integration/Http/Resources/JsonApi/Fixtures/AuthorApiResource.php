<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class AuthorApiResource extends JsonApiResource
{
    protected array $relationships = [
        'comments',
        'profile',
    ];

    #[\Override]
    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    #[\Override]
    public function toType(Request $request)
    {
        return 'authors';
    }
}
