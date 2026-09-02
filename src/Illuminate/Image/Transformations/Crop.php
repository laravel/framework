<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Crop implements Transformation
{
    /**
     * @param  positive-int  $width
     * @param  positive-int  $height
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly int $x = 0,
        public readonly int $y = 0,
    ) {
        //
    }
}
