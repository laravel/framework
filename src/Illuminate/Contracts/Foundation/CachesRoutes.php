<?php

namespace Illuminate\Contracts\Foundation;

interface CachesRoutes
{
    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached();

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath();

    /**
     * Check if the routes cache file loaded.
     *
     * @return bool
     */
    public function isCachedRoutesLoaded();

    /**
     * Load the routes cache file.
     *
     * @return void
     */
    public function loadCachedRoutes();
}
