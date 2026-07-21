<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Brightness implements Transformation
{
    public function __construct(
        public readonly int $amount
    ) {
        //
    }
}
