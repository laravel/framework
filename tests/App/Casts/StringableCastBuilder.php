<?php

namespace Illuminate\Tests\App\Casts;

use Stringable as NativeStringable;

class StringableCastBuilder implements NativeStringable
{
    public $cast = 'int';

    public function __toString()
    {
        return $this->cast;
    }
}
