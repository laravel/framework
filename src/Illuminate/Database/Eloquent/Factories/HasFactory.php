<?php

namespace Illuminate\Database\Eloquent\Factories;

trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @param  callable|array|int|null  $count
     * @param  callable|array  $state
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public static function factory($count = null, $state = [])
    {
        return Factory::factoryForModel(get_called_class())
                    ->count(is_numeric($count) ? $count : null)
                    ->state(is_callable($count) || is_array($count) ? $count : $state);
    }
}
