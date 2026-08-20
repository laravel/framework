<?php

namespace Illuminate\Tests\App\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ProfileResource extends JsonApiResource
{
    protected array $relationships = [
        'user' => UserResource::class,
    ];

    #[\Override]
    public function toAttributes(Request $request)
    {
        return [
            'timezone' => $this->timezone,
            'date_of_birth' => $this->date_of_birth,
        ];
    }
}
