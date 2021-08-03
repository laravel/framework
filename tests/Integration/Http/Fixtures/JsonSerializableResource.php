<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use JsonSerializable;

class JsonSerializableResource implements JsonSerializable
{
    public $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->resource->id,
        ];
    }
}
