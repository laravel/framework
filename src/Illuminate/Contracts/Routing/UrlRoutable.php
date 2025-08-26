<?php

namespace Illuminate\Contracts\Routing;

interface UrlRoutable
{
    /**
     * Get the value of the model's route key.
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
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null);

    /**
     * Retrieve the child model for a bound value.
     *
     * @param  string  $childType
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveChildRouteBinding($childType, $value, $field);
}
