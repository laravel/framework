<?php

namespace Illuminate\Foundation\Exceptions;

use Laravel;
use Illuminate\Support\Arr;
use Illuminate\Filesystem\Filesystem;
use Whoops\Handler\PrettyPageHandler;

class WhoopsHandler
{
    /**
     * Create a new Whoops handler for debug mode.
     *
     * @return \Whoops\Handler\PrettyPageHandler
     */
    public function forDebug()
    {
        return tap(new PrettyPageHandler, function ($handler) {
            $handler->handleUnconditionally(true);

            $this->registerApplicationPaths($handler)
                 ->registerBlacklist($handler)
                 ->registerEditor($handler);
        });
    }

    /**
     * Register the application paths with the handler.
     *
     * @param  \Whoops\Handler\PrettyPageHandler $handler
     * @return $this
     */
    protected function registerApplicationPaths($handler)
    {
        $handler->setApplicationPaths(
            array_flip($this->directoriesExceptVendor())
        );

        return $this;
    }

    /**
     * Get the application paths except for the "vendor" directory.
     *
     * @return array
     */
    protected function directoriesExceptVendor()
    {
        return Arr::except(
            array_flip((new Filesystem)->directories(Laravel::basePath())),
            [Laravel::basePath('vendor')]
        );
    }

    /**
     * Register the blacklist with the handler.
     *
     * @param  \Whoops\Handler\PrettyPageHandler $handler
     * @return $this
     */
    protected function registerBlacklist($handler)
    {
        foreach (Laravel::config('app.debug_blacklist', []) as $key => $secrets) {
            foreach ($secrets as $secret) {
                $handler->blacklist($key, $secret);
            }
        }

        return $this;
    }

    /**
     * Register the editor with the handler.
     *
     * @param  \Whoops\Handler\PrettyPageHandler $handler
     * @return $this
     */
    protected function registerEditor($handler)
    {
        if (Laravel::config('app.editor', false)) {
            $handler->setEditor(Laravel::config('app.editor'));
        }

        return $this;
    }
}
