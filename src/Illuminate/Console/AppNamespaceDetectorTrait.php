<?php

namespace Illuminate\Console;

use Illuminate\Container\Container;

trait AppNamespaceDetectorTrait
{
    /**
     * Get the application namespace.
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }
}
