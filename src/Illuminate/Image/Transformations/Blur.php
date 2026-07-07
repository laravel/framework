<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Blur implements Transformation
{
    public function __construct(public readonly int $amount)
    {
        //
    }
}
