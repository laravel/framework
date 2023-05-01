<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Pipeline\Pipeline send(mixed $passable)
 * @method static \Illuminate\Pipeline\Pipeline through(array|mixed $pipes)
 * @method static \Illuminate\Pipeline\Pipeline pipe(array|mixed $pipes)
 * @method static \Illuminate\Pipeline\Pipeline via(string $method)
 * @method static mixed then(\Closure $destination)
 * @method static mixed thenReturn()
 * @method static \Illuminate\Pipeline\Pipeline setContainer(\Illuminate\Contracts\Container\Container $container)
 *
 * @see \Illuminate\Pipeline\Pipeline
 */
class Pipeline extends Facade
{
    /**
     * Indicates if the resolved instance should be cached.
     *
     * @var bool
     */
    protected static $cached = false;

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pipeline';
    }
}
