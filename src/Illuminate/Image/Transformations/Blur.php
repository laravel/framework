<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Blur implements Transformation
{
    /**
     * @param  non-negative-int  $amount
     */
    public function __construct(public readonly int $amount)
    {
        //
    }
}
