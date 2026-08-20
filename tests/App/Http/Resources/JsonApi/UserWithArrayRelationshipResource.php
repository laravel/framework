<?php

namespace Illuminate\Tests\App\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserWithArrayRelationshipResource extends JsonApiResource
{
    public function toType(Request $request)
    {
        return 'users';
    }

    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
