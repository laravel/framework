<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use JsonSerializable;

class JsonSerializableObject implements JsonSerializable
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
