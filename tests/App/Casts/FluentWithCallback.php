<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Support\Fluent;

class FluentWithCallback extends Fluent
{
    public static function make($attributes = [])
    {
        return new static($attributes);
    }
}
