<?php

namespace Illuminate\Foundation;

use Composer\Script\Event;

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
        static::createSqliteDatabaseFile();
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
     * Clear the cached Laravel bootstrapping files.
     *
     * @return void
     */
    protected static function clearCompiled()
    {
        $laravel = new Application(getcwd());

        if (file_exists($compiledPath = $laravel->getCachedCompilePath())) {
            @unlink($compiledPath);
        }

        if (file_exists($servicesPath = $laravel->getCachedServicesPath())) {
            @unlink($servicesPath);
        }
    }

    /**
     * Create an empty sqlite database file.
     *
     * @return void
     */
    protected static function createSqliteDatabaseFile() {
        $filePath = base_path('database/database.sqlite');
        if ( ! file_exists($filePath) && is_writeable(base_path('database'))) {
            touch($filePath);
        }
    }
}
