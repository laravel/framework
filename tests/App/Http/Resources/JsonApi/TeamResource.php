<?php

namespace Illuminate\Tests\App\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class TeamResource extends JsonApiResource
{
    #[\Override]
    public function toType(Request $request)
    {
        return 'teams';
    }
}
