<?php

namespace Illuminate\Database\Eloquent\Factories;

/**
 * @template TFactory of \Illuminate\Database\Eloquent\Factories\Factory
 */
trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @return TFactory
     */
    public static function factory()
    {
        return static::newFactory() ?? Factory::factoryForModel(static::class);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return TFactory|null
     */
    protected static function newFactory()
    {
        return isset(static::$factory)
            ? static::$factory::new()
            : null;
    }
}
