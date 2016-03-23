<?php

namespace Illuminate\Bootstrap;

use Composer\Script\Event;
use Illuminate\Foundation\Application;

class Composer
{
    public static function postInstall(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require $vendorDir . '/autoload.php';

        static::clearCompiled();
    }

    public static function postUpdate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require $vendorDir . '/autoload.php';

        static::clearCompiled();
    }

    private static function clearCompiled()
    {
        $baseDir = getcwd();
        $laravel = new Application($baseDir);

        $compiledPath = $laravel->getCachedCompilePath();
        $servicesPath = $laravel->getCachedServicesPath();

        if (file_exists($compiledPath)) {
            @unlink($compiledPath);
        }

        if (file_exists($servicesPath)) {
            @unlink($servicesPath);
        }
    }
}
