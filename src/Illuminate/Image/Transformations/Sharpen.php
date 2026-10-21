<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Sharpen implements Transformation
{
    /**
     * @param  int<0, 100>  $amount
     */
    public function __construct(public readonly int $amount)
    {
        //
    }
}
