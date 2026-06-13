<?php

namespace Illuminate\Image\Drivers;

use Intervention\Image\ImageManager;

class GdDriver extends InterventionDriver
{
    /**
     * Create the underlying Intervention image manager.
     */
    protected function createManager(): ImageManager
    {
        return ImageManager::gd();
    }
}
