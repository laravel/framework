<?php

namespace Illuminate\Tests\App\ValueObjects;

class Uuid
{
    public function __construct(private readonly string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
