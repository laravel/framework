<?php

namespace Illuminate\Contracts\Routing;

use Illuminate\Routing\NestedBinding;

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
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  NestedBinding|null  $nestedBinding
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, NestedBinding $nestedBinding = null);
}
