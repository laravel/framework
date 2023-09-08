<?php

namespace Illuminate\Tests\Testing\Stubs;

use JsonSerializable;

class JsonSerializableStubObject implements JsonSerializable
{
    protected $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public static function make($data = [])
    {
        return new self($data);
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}
