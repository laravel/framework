<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Image\Transformation;

class Blur implements Transformation
{
    public function __construct(public readonly int $amount)
    {
        //
    }
}
