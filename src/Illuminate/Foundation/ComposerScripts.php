<?php

namespace Illuminate\Foundation;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Illuminate\Container\Container;
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
     * Run package initialization on the post-autoload-dump Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function runPackageInit(Event $event): void
    {
        $file = self::getInstalledQueueFile($event);

        if (! is_file($file)) {
            return;
        }

        $list = self::getJsonArrayFromFile($file);

        @unlink($file);

        if (! $list) {
            return;
        }

        $app = self::bootstrapLaravelApp($event);

        foreach ($list as $name) {
            $app['events']->dispatch('composer_package.'.$name.':post_install');
        }
    }

    /**
     * Collect the installed package name on the post-package-install Composer event.
     *
     * @param \Composer\Installer\PackageEvent $event
     * @return void
     */
    public static function collectInstalledPackage(PackageEvent $event)
    {
        $file = self::getInstalledQueueFile($event);

        $name = $event->getOperation()->getPackage()->getName();

        $list = [];

        if (is_file($file)) {
            $list = self::getJsonArrayFromFile($file);
        }

        if (in_array($name, $list, true)) {
            return;
        }

        $list[] = $name;

        @mkdir(dirname($file), 0775, true);

        file_put_contents(
            $file,
            json_encode($list),
            LOCK_EX
        );
    }

    /**
     * Read JSON file as array.
     *
     * @param  string  $file
     * @return array
     */
    private static function getJsonArrayFromFile(string $file)
    {
        $json = file_get_contents($file);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Handle the pre-package-uninstall Composer event.
     *
     * @param  \Composer\Installer\PackageEvent  $event
     * @return void
     */
    public static function prePackageUninstall(PackageEvent $event)
    {
        $app = self::bootstrapLaravelApp($event);

        /** @var \Composer\DependencyResolver\Operation\UninstallOperation $uninstallOperation */
        $uninstallOperation = $event->getOperation()->getPackage();

        $app['events']->dispatch('composer_package.'.$uninstallOperation->getName().':pre_uninstall');
    }

    /**
     * Bootstrap Laravel application from Composer event.
     *
     * @param  \Composer\Script\Event|\Composer\Installer\PackageEvent  $event
     * @return \Illuminate\Foundation\Application|null
     */
    private static function bootstrapLaravelApp(Event|PackageEvent $event): ?Application
    {
        $vendorDir = self::getVendorDir($event);
        $bootstrapFile = dirname($vendorDir).'/bootstrap/app.php';

        if (! file_exists($bootstrapFile)) {
            return null;
        }

        require_once $vendorDir.'/autoload.php';

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        require_once $bootstrapFile;

        /** @var Application $app */
        $app = Container::getInstance();
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Get the path to the installed packages queue file.
     *
     * @param  \Composer\Script\Event|\Composer\Installer\PackageEvent  $event
     * @return string
     */
    private static function getInstalledQueueFile(Event|PackageEvent $event): string
    {
        return dirname(self::getVendorDir($event)).'/bootstrap/cache/composer-installed-package.json';
    }

    /**
     * Get the Composer vendor directory path from the given event.
     *
     * @param  Event|\Composer\Installer\PackageEvent  $event
     * @return string
     */
    private static function getVendorDir(Event|PackageEvent $event): string
    {
        return $event->getComposer()->getConfig()->get('vendor-dir');
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
