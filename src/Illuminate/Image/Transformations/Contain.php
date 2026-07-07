<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Contain implements Transformation
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly ?string $background = null,
    ) {
        //
    }
}
