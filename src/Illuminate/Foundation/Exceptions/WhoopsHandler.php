<?php

namespace Illuminate\Foundation\Exceptions;

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
            array_flip((new Filesystem)->directories(base_path())),
            [base_path('vendor')]
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
        $whitelist = config('app.debug_whitelist', []);
        $all_superglob  = [
            '_GET'      =>  $_GET ?? [],
            '_POST'     =>  $_POST ?? [],
            '_FILES'    =>  $_FILES ?? [],
            '_COOKIE'   =>  $_COOKIE ?? [],
            '_SESSION'  =>  $_SESSION ?? [],
            '_SERVER'   =>  $_SERVER ?? [],
            '_ENV'      =>  $_ENV ?? [],
        ];

        $missing_superglob = array_diff_key($all_superglob,$whitelist);
        foreach ($missing_superglob as $missing_key => $values) {
            // If you want to be absolutely sure not to overrite anything:
            if (!array_key_exists($missing_key, $whitelist)) {
                $whitelist[$missing_key] = [];
            }
        }

        foreach ($whitelist as $superglob_key => $whitelisted_items) {
            if(is_array($whitelisted_items) || ($whitelisted_items !== '*')) {
                $blacklist = array_except($all_superglob[$superglob_key], $whitelisted_items);
                foreach ($blacklist as $secret => $value) {
                    $handler->blacklist($superglob_key, $secret);
                }
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
