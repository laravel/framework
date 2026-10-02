<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Blur implements Transformation
{
    /**
     * @param  positive-int  $amount
     */
    public function __construct(public readonly int $amount)
    {
        //
    }
}
