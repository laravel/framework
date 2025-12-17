<?php

namespace Illuminate\Tests\Support\Fixtures;

class StringableObjectStub
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
