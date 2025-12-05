<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserApiResource extends JsonApiResource
{
    protected array $relationships = [
        'profile',
        'posts',
        'teams',
    ];

    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
