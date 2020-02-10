<?php

namespace Illuminate\Support\Facades;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;

/**
 * @method static EloquentFactory defineAs($class, $name, callable $attributes)
 * @method static EloquentFactory define($class, callable $attributes, $name = 'default')
 * @method static EloquentFactory state($class, $state, $attributes)
 * @method static EloquentFactory afterMaking($class, callable $callback, $name = 'default')
 * @method static EloquentFactory afterMakingState($class, $state, callable $callback)
 * @method static EloquentFactory afterCreating($class, callable $callback, $name = 'default')
 * @method static EloquentFactory afterCreatingState($class, $state, callable $callback)
 * @method static mixed create($class, array $attributes = [])
 * @method static mixed createAs($class, $name, array $attributes = [])
 * @method static mixed make($class, array $attributes = [])
 * @method static mixed makeAs($class, $name, array $attributes = [])
 * @method static array rawOf($class, $name, array $attributes = [])
 * @method static array raw($class, array $attributes = [], $name = 'default')
 * @method static \Illuminate\Database\Eloquent\FactoryBuilder of($class, $name = 'default')
 * @method static EloquentFactory load($path)
 *
 * @see EloquentFactory
 */
class Factory extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return EloquentFactory::class;
    }
}
