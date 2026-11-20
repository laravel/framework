<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserWithLazyTeamAttributeResource extends JsonApiResource
{
    protected array $relationships = [
        'teams' => TeamWithLazyAttributeResource::class,
    ];

    public function toType(Request $request)
    {
        return 'users';
    }

    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
        ];
    }
}
