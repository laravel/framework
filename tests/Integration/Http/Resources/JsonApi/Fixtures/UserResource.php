<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
