<?php

namespace Illuminate\Auth\Access;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class Gate implements GateContract
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The user instance.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;

    /**
     * All of the defined abilities.
     *
     * @var array
     */
    protected $abilities = [];

    /**
     * All of the defined policies.
     *
     * @var array
     */
    protected $policies = [];

    /**
     * Create a new gate instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  array  $abilities
     * @param  array  $policies
     * @return void
     */
    public function __construct(Container $container, $user, array $abilities = [], array $policies = [])
    {
        $this->user = $user;
        $this->policies = $policies;
        $this->container = $container;
        $this->abilities = $abilities;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * @param  string  $ability
     * @return bool
     */
    public function has($ability)
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Define a new ability.
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     */
    public function define($ability, $callback)
    {
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback) && str_contains($callback, '@')) {
            $this->abilities[$ability] = $this->buildAbilityCallback($callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
    }

    /**
     * Create the ability callback for a callback string.
     *
     * @param  string  $callback
     * @return \Closure
     */
    protected function buildAbilityCallback($callback)
    {
        return function () use ($callback) {
            list($class, $method) = explode('@', $callback);

            return call_user_func_array([$this->resolvePolicy($class), $method], func_get_args());
        };
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param  string  $class
     * @param  string  $policy
     * @return $this
     */
    public function policy($class, $policy)
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguents
     * @return bool
     */
    public function check($ability, $arguments = [])
    {
        if (! $this->user) {
            return false;
        }

        if (! is_array($arguments)) {
            $arguments = [$arguments];
        }

        if (isset($arguments[0]) && isset($this->policies[$argumentClass = get_class($arguments[0])])) {
            $callback = [$this->resolvePolicy($this->policies[$argumentClass]), $ability];
        } elseif (isset($this->abilities[$ability])) {
            $callback = $this->abilities[$ability];
        } else {
            throw new InvalidArgumentException("Ability [{$ability}] has not been defined.");
        }

        array_unshift($arguments, $this->user);

        return call_user_func_array($callback, $arguments);
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getPolicyFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (! isset($this->policies[$class])) {
            throw new InvalidArgumentException("Policy not defined for [{$class}].");
        }

        return $this->resolvePolicy($this->policies[$class]);
    }
    /**
     * Build a policy class instance of the given type.
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function resolvePolicy($class)
    {
        return $this->container->make($class);
    }

    /**
     * Get a guard instance for the given uer.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user)
    {
        return new static(
            $this->container, $user, $this->abilities, $this->policies
        );
    }
}
