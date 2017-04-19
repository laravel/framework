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
}
