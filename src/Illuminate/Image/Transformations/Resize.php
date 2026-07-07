<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Resize implements Transformation
{
    public function __construct(
        public readonly ?int $width,
        public readonly ?int $height,
    ) {
        //
    }
}
