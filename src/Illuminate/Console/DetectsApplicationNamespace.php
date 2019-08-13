<?php

namespace Illuminate\Console;

use Illuminate\Container\Container;

/**
 * @deprecated Usage of this trait is deprecated and it will be removed in Laravel 7.0.
 */
trait DetectsApplicationNamespace
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
