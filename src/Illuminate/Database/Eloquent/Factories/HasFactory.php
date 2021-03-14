<?php

namespace Illuminate\Database\Eloquent\Factories;

trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @param  mixed  $parameters
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public static function factory(...$parameters)
    {
        $factory = self::getNewFactory() ?: Factory::factoryForModel(get_called_class());

        return $factory
                    ->count(is_numeric($parameters[0] ?? null) ? $parameters[0] : null)
                    ->state(is_array($parameters[0] ?? null) ? $parameters[0] : ($parameters[1] ?? []));
    }

    /**
     * Get factory instance or namespace
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|string
     */
    protected static function newFactory()
    {
        //
    }
    
    /**
     * Return Factory instance
     * @return mixed
     */
    private static function getNewFactory()
    {
        $newFactory = static::newFactory();

        if(!$newFactory){
            return;
        }

        return ($newFactory instanceof Factory)
            ? $newFactory
            : app()->make($newFactory);
    }
}
