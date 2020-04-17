<?php

namespace Illuminate\Tests\Support\Fixtures;

class CustomDateClass
{
    protected $original;

    public function __construct($original)
    {
        $this->original = $original;
    }

    public static function instance($original)
    {
        return new static($original);
    }

    public function getOriginal()
    {
        return $this->original;
    }
}
