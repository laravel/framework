<?php

namespace Illuminate\Image\Drivers;

use Intervention\Image\Drivers\Gd\Driver as InterventionGdDriver;
use Intervention\Image\ImageManager;

class GdDriver extends InterventionDriver
{
    /**
     * Create the underlying Intervention image manager.
     */
    protected function createManager(): ImageManager
    {
        return ImageManager::usingDriver(InterventionGdDriver::class);
    }
}
