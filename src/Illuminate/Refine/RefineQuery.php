<?php

namespace Illuminate\Refine;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

use function array_diff;
use function is_callable;

class RefineQuery
{
    /**
     * The container resolver.
     *
     * @var callable(string): mixed
     */
    protected static $resolver;

    /**
     * Create a new refine query instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Refine\Refiner  $refiner
     */
    public function __construct(protected $builder, protected $refiner)
    {
        //
    }

    /**
     * Refine the query using the HTTP Request query.
     *
     * @param  array|null  $data
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function refine(array $data = null)
    {
        $data ??= $this->request()->query();

        $request = $this->request();

        $this->refiner->before($this->builder, $request);

        // Retrieve the keys to use for refinement without the default methods
        $keys = array_values(array_diff($this->refiner->keys($request), ['keys', 'before', 'after']));

        foreach (Arr::only($data, $keys) as $key => $value) {
            $method = Str::camel($key);

            if (is_callable([$this->refiner, $method])) {
                $this->refiner->{$method}($this->builder, $value, $request);
            }
        }

        $this->refiner->after($this->builder, $request);

        return $this->builder;
    }

    /**
     * Resolve an abstract from the container.
     *
     * @param  string  $abstract
     * @return mixed
     */
    protected static function resolve(string $abstract)
    {
        return (static::$resolver)($abstract);
    }

    /**
     * Retrieve the current request.
     *
     * @return \Illuminate\Http\Request
     */
    public function request()
    {
        return static::resolve('request');
    }

    /**
     * Sets a resolver callable for the given container.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     * @return void
     */
    public static function setResolver($app)
    {
        static::$resolver = fn ($abstract) => $app->make($abstract);
    }

    /**
     * Unsets the callable resolver.
     *
     * @return void
     */
    public static function unsetResolver()
    {
        static::$resolver = null;
    }

    /**
     * Create a new refine query instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  class-string|string  $refiner
     * @return static
     */
    public static function make($builder, string $refiner): static
    {
        return new static($builder, static::resolve($refiner));
    }
}
