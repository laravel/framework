<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class SerializablePostResource extends JsonResource
{
    public function toArray($request)
    {
        return new JsonSerializableResource($this);
    }
}
