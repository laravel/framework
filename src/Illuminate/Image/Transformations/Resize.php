<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Resize implements Transformation
{
    /**
     * @param  positive-int|null  $width
     * @param  positive-int|null  $height
     */
    public function __construct(
        public readonly ?int $width,
        public readonly ?int $height,
    ) {
        //
    }
}
