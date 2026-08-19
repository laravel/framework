<?php

namespace Illuminate\Tests\App\Models\Relationships;

class EloquentTestKey
{
    public function __construct(private readonly string $key)
    {
    }

    public function __toString()
    {
        return $this->key;
    }
}
