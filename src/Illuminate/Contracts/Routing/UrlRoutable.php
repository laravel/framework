<?php

namespace Illuminate\Contracts\Routing;

interface UrlRoutable
{
    /**
     * Get the value of the model's route key.
     *
     * @return mixed
     */
    public function getRouteKey();

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName();

    /**
     * Mutate route param back to the model's key value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function mutateRouteKey($key);
}
