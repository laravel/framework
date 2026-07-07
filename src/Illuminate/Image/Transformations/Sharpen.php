<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Image\Transformation;

class Sharpen implements Transformation
{
    public function __construct(public readonly int $amount)
    {
        //
    }
}
