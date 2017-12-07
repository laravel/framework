<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\Resource;

class SerializablePostResource extends Resource
{
    public function toArray($request)
    {
        return new JsonSerializableResource($this);
    }
}
