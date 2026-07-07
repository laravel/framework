<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Image\Transformation;

class Cover implements Transformation
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {
        //
    }
}
