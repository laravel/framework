<?php

namespace Illuminate\Support\Traits;

trait Pipeable
{
    public function pipe(callable $callback)
    {
        return $callback($this);
    }
}
