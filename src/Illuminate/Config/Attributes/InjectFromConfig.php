<?php

namespace Illuminate\Config\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\DependencyResolver;

#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectFromConfig implements DependencyResolver
{
    /**
     * Key to get from config.
     *
     * @var string|array
     */
    private $key;

    /**
     * Default in case config is not present or bound.
     *
     * @var mixed
     */
    private $default;

    /**
     * Create a new InjectFromConfig instance.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return void
     */
    public function __construct($key, $default = null)
    {
        $this->key = $key;
        $this->default = $default;
    }

    /**
     * Resolve the dependency from the container.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function resolve(Container $container): mixed
    {
        if (! $container->bound('config')) {
            return value($this->default);
        }

        return $container->make('config')->get($this->key, $this->default);
    }
}
