<?php

namespace Illuminate\Foundation;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Illuminate\Contracts\Console\Kernel;

class ComposerScripts
{
    /**
     * Handle the post-install Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postInstall(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::clearCompiled();
    }

    /**
     * Handle the post-update Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postUpdate(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::clearCompiled();
    }

    /**
     * Handle the post-autoload-dump Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postAutoloadDump(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::clearCompiled();
    }

    /**
     * Handle the pre-package-uninstall Composer event.
     *
     * @param  \Composer\Installer\PackageEvent  $event
     * @return void
     */
    public static function prePackageUninstall(PackageEvent $event)
    {
        $bootstrapFile = dirname($vendorDir = $event->getComposer()->getConfig()->get('vendor-dir')).'/bootstrap/app.php';

        if (! file_exists($bootstrapFile)) {
            return;
        }

        require_once $vendorDir.'/autoload.php';

        define('LARAVEL_START', microtime(true));

        /** @var Application $app */
        $app = require_once $bootstrapFile;

        $app->make(Kernel::class)->bootstrap();

        /** @var \Composer\DependencyResolver\Operation\UninstallOperation $uninstallOperation */
        $uninstallOperation = $event->getOperation()->getPackage();

        $app['events']->dispatch('composer_package.'.$uninstallOperation->getName().':pre_uninstall');
    }

    /**
     * Clear the cached Laravel bootstrapping files.
     *
     * @return void
     */
    protected static function clearCompiled()
    {
        $laravel = new Application(getcwd());

        if (is_file($configPath = $laravel->getCachedConfigPath())) {
            @unlink($configPath);
        }

        if (is_file($servicesPath = $laravel->getCachedServicesPath())) {
            @unlink($servicesPath);
        }

        if (is_file($packagesPath = $laravel->getCachedPackagesPath())) {
            @unlink($packagesPath);
        }
    }
}
