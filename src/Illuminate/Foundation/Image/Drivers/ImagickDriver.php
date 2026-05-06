<?php

namespace Illuminate\Foundation\Image\Drivers;

use Intervention\Image\ImageManager;

class ImagickDriver extends InterventionDriver
{
    /**
     * Create the underlying Intervention image manager.
     */
    protected function createManager(): ImageManager
    {
        return ImageManager::imagick();
    }
}
