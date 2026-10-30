<?php

namespace Illuminate\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

class Rotate implements Transformation
{
    public function __construct(
        public readonly float $angle,
        public readonly ?string $background = null,
    ) {
        //
    }
}
