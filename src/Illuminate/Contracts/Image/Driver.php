<?php

namespace Illuminate\Contracts\Image;

use Illuminate\Image\ImagePipeline;

interface Driver
{
    /**
     * Process the given image contents with the specified pipeline.
     */
    public function process(string $contents, ImagePipeline $pipeline): string;
}
