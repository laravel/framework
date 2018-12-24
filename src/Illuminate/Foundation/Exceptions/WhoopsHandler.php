<?php

namespace Illuminate\Foundation\Exceptions;

use Illuminate\Support\Arr;
use Illuminate\Filesystem\Filesystem;
use Whoops\Handler\PrettyPageHandler;
use Dotenv\Dotenv;

class WhoopsHandler
{
    /**
     * The superglobals to blacklist env keys in.
     *
     * @var array
     */
    const BLACKLISTED_SUPERGLOBALS = [
        '_ENV',
        '_SERVER',
    ];

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
                 ->registerEnvBlacklist($handler)
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
            array_flip((new Filesystem)->directories(base_path())),
            [base_path('vendor')]
        );
    }

    /**
     * Register the app blacklist with the handler.
     *
     * @param  \Whoops\Handler\PrettyPageHandler $handler
     * @return $this
     */
    protected function registerBlacklist($handler)
    {
        foreach (config('app.debug_blacklist', []) as $key => $secrets) {
            foreach ($secrets as $secret) {
                $handler->blacklist($key, $secret);
            }
        }

        return $this;
    }

    /**
     * Register the env file blacklist with the handler.
     *
     * @param  \Whoops\Handler\PrettyPageHandler $handler
     * @return $this
     */
    protected function registerEnvBlacklist($handler)
    {
        $dotenv = new Dotenv(base_path());
        $dotenv->safeLoad();
        
        foreach ($dotenv->getEnvironmentVariableNames() as $key) {
            if (in_array($key, config('app.debug_whitelist'))) {
                continue;
            }

            foreach (self::BLACKLISTED_SUPERGLOBALS as $superglobal) {
                $handler->blacklist($superglobal, $key);
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
        if (config('app.editor', false)) {
            $handler->setEditor(config('app.editor'));
        }

        return $this;
    }
}
