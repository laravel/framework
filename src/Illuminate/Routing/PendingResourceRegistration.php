<?php

namespace Illuminate\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use ReflectionClass;
use ReflectionMethod;

class PendingResourceRegistration
{
    use CreatesRegularExpressionRouteConstraints, Macroable;

    /**
     * The resource registrar.
     *
     * @var \Illuminate\Routing\ResourceRegistrar
     */
    protected $registrar;

    /**
     * The resource name.
     *
     * @var string
     */
    protected $name;

    /**
     * The resource controller.
     *
     * @var string
     */
    protected $controller;

    /**
     * The resource options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The resource's registration status.
     *
     * @var bool
     */
    protected $registered = false;

    /**
     * Create a new pending resource registration instance.
     *
     * @param  \Illuminate\Routing\ResourceRegistrar  $registrar
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return void
     */
    public function __construct(ResourceRegistrar $registrar, $name, $controller, array $options)
    {
        $this->name = $name;
        $this->options = $options;
        $this->registrar = $registrar;
        $this->controller = $controller;
    }

    /**
     * Set the methods the controller should apply to.
     *
     * @param  array|string|mixed  $methods
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function only($methods)
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }

    /**
     * Set the methods the controller should exclude.
     *
     * @param  array|string|mixed  $methods
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function except($methods)
    {
        $this->options['except'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }

    /**
     * Set the route names for controller actions.
     *
     * @param  array|string  $names
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function names($names)
    {
        $this->options['names'] = $names;

        return $this;
    }

    /**
     * Set the route name for a controller action.
     *
     * @param  string  $method
     * @param  string  $name
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function name($method, $name)
    {
        $this->options['names'][$method] = $name;

        return $this;
    }

    /**
     * Override the route parameter names.
     *
     * @param  array|string  $parameters
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function parameters($parameters)
    {
        $this->options['parameters'] = $parameters;

        return $this;
    }

    /**
     * Override a route parameter's name.
     *
     * @param  string  $previous
     * @param  string  $new
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function parameter($previous, $new)
    {
        $this->options['parameters'][$previous] = $new;

        return $this;
    }

    /**
     * Add middleware to the resource routes.
     *
     * @param  mixed  $middleware
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function middleware($middleware)
    {
        $middleware = Arr::wrap($middleware);

        foreach ($middleware as $key => $value) {
            $middleware[$key] = (string) $value;
        }

        $this->options['middleware'] = $middleware;

        return $this;
    }

    /**
     * Specify middleware that should be removed from the resource routes.
     *
     * @param  array|string  $middleware
     * @return $this|array
     */
    public function withoutMiddleware($middleware)
    {
        $this->options['excluded_middleware'] = array_merge(
            (array) ($this->options['excluded_middleware'] ?? []), Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Add "where" constraints to the resource routes.
     *
     * @param  mixed  $wheres
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function where($wheres)
    {
        $this->options['wheres'] = $wheres;

        return $this;
    }

    /**
     * Indicate that the resource routes should have "shallow" nesting.
     *
     * @param  bool  $shallow
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function shallow($shallow = true)
    {
        $this->options['shallow'] = $shallow;

        return $this;
    }

    /**
     * Define the callable that should be invoked on a missing model exception.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function missing($callback)
    {
        $this->options['missing'] = $callback;

        return $this;
    }

    /**
     * Indicate that the resource routes should be scoped using the given binding fields.
     *
     * @param  array  $fields
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function scoped(array $fields = [])
    {
        $this->options['bindingFields'] = $fields;

        return $this;
    }

    /**
     * Define which routes should allow "trashed" models to be retrieved when resolving implicit model bindings.
     *
     * @param  array  $methods
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function withTrashed(array $methods = [])
    {
        $this->options['trashed'] = $methods;

        return $this;
    }

    /**
     * Specify the policy to be used for authorizing requests.
     *
     * @param  class-string|string|array<string>  $classOrAbilities
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function authorize($classOrAbilities = [])
    {
        if (empty($classOrAbilities)) {
            $this->options['abilities'] = $this->resourceAbilityMap();

            return $this;
        }

        $abilities = is_string($classOrAbilities) && $this->isClassName($classOrAbilities)
                ? $this->resolveAbilitiesFor($classOrAbilities)
                : $this->resolveAbilities(
                    is_array($classOrAbilities) ? $classOrAbilities : func_get_args()
                );

        $this->options['abilities'] = $abilities;

        return $this;
    }

    /**
     * Register the resource route.
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    public function register()
    {
        $this->registered = true;

        return $this->registrar->register(
            $this->name, $this->controller, $this->options
        );
    }

    /**
     * Get the abilities that are available for the given class.
     *
     * @param  class-string  $class
     * @return array
     */
    protected function resolveAbilitiesFor($class)
    {
        $policy = $this->getPolicyFor($class)[0];

        if (! class_exists($policy)) {
            return [];
        }

        $abilities = [];

        $reflectionMethods = (new ReflectionClass($policy))
            ->getMethods(ReflectionMethod::IS_PUBLIC);

        $policyMethods = collect($reflectionMethods)
            ->reject(fn ($method) => str_starts_with($method->name, '__'))
            ->map(fn ($method) => $method->name)
            ->all();

        foreach ($this->resourceAbilityMap() as $method => $ability) {
            if (! in_array($ability, $policyMethods)) {
                continue;
            }

            $abilities[$method] = $ability;
        }

        return $abilities;
    }

    /**
     * Resolves the abilities for the given input.
     *
     * @param  array<string>  $input
     * @return array
     */
    protected function resolveAbilities($input)
    {
        $abilities = [];

        foreach ($this->resourceAbilityMap() as $method => $ability) {
            if (in_array($method, $input)) {
                $abilities[$method] = $ability;

                continue;
            }

            if (! in_array($ability, $input)) {
                continue;
            }

            $abilities[$method] = $ability;
        }

        return $abilities;
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function getPolicyFor($class)
    {
        if (strpos($class, 'Policy')) {
            return Arr::wrap($class);
        }

        return $this->guessPolicyName($class) ?: [];
    }

    /**
     * Guess the policy name for the given class.
     *
     * @param  string  $class
     * @return array
     */
    protected function guessPolicyName($class)
    {
        $classDirname = str_replace('/', '\\', dirname(str_replace('\\', '/', $class)));

        $classDirnameSegments = explode('\\', $classDirname);

        if (Arr::last($classDirnameSegments) === 'Models') {
            array_pop($classDirnameSegments);
        }

        return Arr::wrap(Collection::times(count($classDirnameSegments), function ($index) use ($class, $classDirnameSegments) {
            $classDirname = implode('\\', array_slice($classDirnameSegments, 0, $index));

            return $classDirname.'\\Policies\\'.class_basename($class).'Policy';
        })->reverse()->values()->first(function ($class) {
            return class_exists($class);
        }) ?: [$classDirname.'\\Policies\\'.class_basename($class).'Policy']);
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }

    /**
     * Checks if the given string looks like a fully qualified class name.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isClassName($value)
    {
        return str_contains($value, '\\');
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        if (! $this->registered) {
            $this->register();
        }
    }
}
