<?php

namespace Illuminate\Database\Eloquent\TypeCaster;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Database\TypeCaster\Factory as FactoryContract;

class Factory implements FactoryContract
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * All of the custom Type Caster extensions.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Create a new Type Caster factory instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Create a new Type Caster instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\TypeCaster\TypeCaster
     */
    public function make(Model $model)
    {
        $typecaster = new TypeCaster($model);

        // Next we'll set the IoC container instance of the type caster, which is used 
        // to resolve out class based type casting extensions. If it is not set then 
        // these extension types wont be possible on these type caster instances.
        if (! is_null($this->container)) {
            $typecaster->setContainer($this->container);
        }

        $typecaster->addExtensions($this->extensions);

        return $typecaster;
    }

    /**
     * Register a custom Type Caster extension.
     *
     * @param  string  $rule
     * @param  \Closure|string  $fromDatabase
     * @param  \Closure|string|null  $toDatabase
     * @return void
     */
    public function extend($rule, $fromDatabase, $toDatabase = null)
    {
        $this->extensions[$rule] = [
            'from' => $fromDatabase,
            'to' => $toDatabase,
        ];
    }
}
