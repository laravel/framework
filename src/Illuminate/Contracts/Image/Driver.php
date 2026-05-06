<?php

namespace Illuminate\Contracts\Image;

use Illuminate\Foundation\Image\PendingImageOptions;

interface Driver
{
    /**
     * Process the given image contents with the specified options.
     */
    public function process(string $contents, PendingImageOptions $options): string;
}
