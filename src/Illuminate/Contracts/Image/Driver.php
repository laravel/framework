<?php

namespace Illuminate\Contracts\Image;

use Illuminate\Foundation\Image\PendingImageOptions;

interface Driver
{
    /**
     * Process an image at the given path with the specified options.
     */
    public function process(string $sourcePath, PendingImageOptions $options): string;
}
