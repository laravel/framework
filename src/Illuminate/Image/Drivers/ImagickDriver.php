<?php

namespace Illuminate\Image\Drivers;

use Intervention\Image\Drivers\Imagick\Driver as InterventionImagickDriver;
use Intervention\Image\ImageManager;

class ImagickDriver extends InterventionDriver
{
    /**
     * Create the underlying Intervention image manager.
     */
    protected function createManager(): ImageManager
    {
        return ImageManager::usingDriver(InterventionImagickDriver::class);
    }
}
