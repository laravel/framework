<?php

namespace Illuminate\Auth\Access;

use Exception;
use ReflectionClass;
use ReflectionFunction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class Gate implements GateContract
{
    use HandlesAuthorization;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The user resolver callable.
     *
     * @var callable
     */
    protected $userResolver;

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
     * All of the registered before callbacks.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * All of the registered after callbacks.
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * All of the defined abilities using class@method notation.
     *
     * @var array
     */
    protected $stringCallbacks = [];

    /**
     * The callback to be used to guess policy names.
     *
     * @var callable|null
     */
    protected $guessPolicyNamesUsingCallback;

    /**
     * Create a new gate instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  callable  $userResolver
     * @param  array  $abilities
     * @param  array  $policies
     * @param  array  $beforeCallbacks
     * @param  array  $afterCallbacks
     * @param  callable  $guessPolicyNamesUsingCallback
     * @return void
     */
    public function __construct(Container $container, callable $userResolver, array $abilities = [],
                                array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [],
                                callable $guessPolicyNamesUsingCallback = null)
    {
        $this->policies = $policies;
        $this->container = $container;
        $this->abilities = $abilities;
        $this->userResolver = $userResolver;
        $this->afterCallbacks = $afterCallbacks;
        $this->beforeCallbacks = $beforeCallbacks;
        $this->guessPolicyNamesUsingCallback = $guessPolicyNamesUsingCallback;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * @param  string|array  $ability
     * @return bool
     */
    public function has($ability)
    {
        $abilities = is_array($ability) ? $ability : func_get_args();

        foreach ($abilities as $ability) {
            if (! isset($this->abilities[$ability])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Define a new ability.
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback)) {
            $this->stringCallbacks[$ability] = $callback;

            $this->abilities[$ability] = $this->buildAbilityCallback($ability, $callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
    }

    /**
     * Define abilities for a resource.
     *
     * @param  string  $name
     * @param  string  $class
     * @param  array|null   $abilities
     * @return $this
     */
    public function resource($name, $class, array $abilities = null)
    {
        $abilities = $abilities ?: [
            'view'   => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
        ];

        foreach ($abilities as $ability => $method) {
            $this->define($name.'.'.$ability, $class.'@'.$method);
        }

        return $this;
    }

    /**
     * Create the ability callback for a callback string.
     *
     * @param  string  $ability
     * @param  string  $callback
     * @return \Closure
     */
    protected function buildAbilityCallback($ability, $callback)
    {
        return function () use ($ability, $callback) {
            if (Str::contains($callback, '@')) {
                [$class, $method] = Str::parseCallback($callback);
            } else {
                $class = $callback;
            }

            $policy = $this->resolvePolicy($class);

            $arguments = func_get_args();

            $user = array_shift($arguments);

            $result = $this->callPolicyBefore(
                $policy, $user, $ability, $arguments
            );

            if (! is_null($result)) {
                return $result;
            }

            return isset($method)
                    ? $policy->{$method}(...func_get_args())
                    : $policy(...func_get_args());
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
     * Register a callback to run before all Gate checks.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after(callable $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return ! $this->allows($ability, $arguments);
    }

    /**
     * Determine if all of the given abilities should be granted for the current user.
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function check($abilities, $arguments = [])
    {
        return collect($abilities)->every(function ($ability) use ($arguments) {
            try {
                return (bool) $this->raw($ability, $arguments);
            } catch (AuthorizationException $e) {
                return false;
            }
        });
    }

    /**
     * Determine if any one of the given abilities should be granted for the current user.
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function any($abilities, $arguments = [])
    {
        return collect($abilities)->contains(function ($ability) use ($arguments) {
            return $this->check($ability, $arguments);
        });
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        $result = $this->raw($ability, $arguments);

        if ($result instanceof Response) {
            return $result;
        }

        return $result ? $this->allow() : $this->deny();
    }

    /**
     * Get the raw result from the authorization callback.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return mixed
     */
    public function raw($ability, $arguments = [])
    {
        $arguments = Arr::wrap($arguments);

        $user = $this->resolveUser();

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
        $result = $this->callBeforeCallbacks(
            $user, $ability, $arguments
        );

        if (is_null($result)) {
            $result = $this->callAuthCallback($user, $ability, $arguments);
        }

        // After calling the authorization callback, we will call the "after" callbacks
        // that are registered with the Gate, which allows a developer to do logging
        // if that is required for this application. Then we'll return the result.
        return $this->callAfterCallbacks(
            $user, $ability, $arguments, $result
        );
    }

    /**
     * Determine whether the callback/method can be called with the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  \Closure|string|array  $class
     * @param  string|null $method
     * @return bool
     */
    protected function canBeCalledWithUser($user, $class, $method = null)
    {
        if (! is_null($user)) {
            return true;
        }

        if (! is_null($method)) {
            return $this->methodAllowsGuests($class, $method);
        }

        if (is_array($class)) {
            $className = is_string($class[0]) ? $class[0] : get_class($class[0]);

            return $this->methodAllowsGuests($className, $class[1]);
        }

        return $this->callbackAllowsGuests($class);
    }

    /**
     * Determine if the given class method allows guests.
     *
     * @param  string  $class
     * @param  string  $method
     * @return bool
     */
    protected function methodAllowsGuests($class, $method)
    {
        try {
            $reflection = new ReflectionClass($class);

            $method = $reflection->getMethod($method);
        } catch (Exception $e) {
            return false;
        }

        if ($method) {
            $parameters = $method->getParameters();

            return isset($parameters[0]) && $this->parameterAllowsGuests($parameters[0]);
        }

        return false;
    }

    /**
     * Determine if the callback allows guests.
     *
     * @param  callable  $callback
     * @return bool
     */
    protected function callbackAllowsGuests($callback)
    {
        $parameters = (new ReflectionFunction($callback))->getParameters();

        return isset($parameters[0]) && $this->parameterAllowsGuests($parameters[0]);
    }

    /**
     * Determine if the given parameter allows guests.
     *
     * @param  \ReflectionParameter  $parameter
     * @return bool
     */
    protected function parameterAllowsGuests($parameter)
    {
        return ($parameter->getClass() && $parameter->allowsNull()) ||
               ($parameter->isDefaultValueAvailable() && is_null($parameter->getDefaultValue()));
    }

    /**
     * Resolve and call the appropriate authorization callback.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool
     */
    protected function callAuthCallback($user, $ability, array $arguments)
    {
        $callback = $this->resolveAuthCallback($user, $ability, $arguments);

        return $callback($user, ...$arguments);
    }

    /**
     * Call all of the before callbacks and return if a result is given.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool|null
     */
    protected function callBeforeCallbacks($user, $ability, array $arguments)
    {
        $arguments = array_merge([$user, $ability], [$arguments]);

        foreach ($this->beforeCallbacks as $before) {
            if (! $this->canBeCalledWithUser($user, $before)) {
                continue;
            }

            if (! is_null($result = $before(...$arguments))) {
                return $result;
            }
        }
    }

    /**
     * Call all of the after callbacks with check result.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  bool  $result
     * @return bool|null
     */
    protected function callAfterCallbacks($user, $ability, array $arguments, $result)
    {
        foreach ($this->afterCallbacks as $after) {
            if (! $this->canBeCalledWithUser($user, $after)) {
                continue;
            }

            $afterResult = $after($user, $ability, $result, $arguments);

            $result = $result ?? $afterResult;
        }

        return $result;
    }

    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolveAuthCallback($user, $ability, array $arguments)
    {
        if (isset($arguments[0]) &&
            ! is_null($policy = $this->getPolicyFor($arguments[0])) &&
            $callback = $this->resolvePolicyCallback($user, $ability, $arguments, $policy)) {
            return $callback;
        }

        if (isset($this->stringCallbacks[$ability])) {
            [$class, $method] = Str::parseCallback($this->stringCallbacks[$ability]);

            if ($this->canBeCalledWithUser($user, $class, $method ?: '__invoke')) {
                return $this->abilities[$ability];
            }
        }

        if (isset($this->abilities[$ability]) &&
            $this->canBeCalledWithUser($user, $this->abilities[$ability])) {
            return $this->abilities[$ability];
        }

        return function () {
            return null;
        };
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function getPolicyFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (! is_string($class)) {
            return;
        }

        if (isset($this->policies[$class])) {
            return $this->resolvePolicy($this->policies[$class]);
        }

        foreach ($this->guessPolicyName($class) as $guessedPolicy) {
            if (class_exists($guessedPolicy)) {
                return $this->resolvePolicy($guessedPolicy);
            }
        }

        foreach ($this->policies as $expected => $policy) {
            if (is_subclass_of($class, $expected)) {
                return $this->resolvePolicy($policy);
            }
        }
    }

    /**
     * Guess the policy name for the given class.
     *
     * @param  string  $class
     * @return array
     */
    protected function guessPolicyName($class)
    {
        if ($this->guessPolicyNamesUsingCallback) {
            return Arr::wrap(call_user_func($this->guessPolicyNamesUsingCallback, $class));
        }

        $classDirname = str_replace('/', '\\', dirname(str_replace('\\', '/', $class)));

        return [$classDirname.'\\Policies\\'.class_basename($class).'Policy'];
    }

    /**
     * Specify a callback to be used to guess policy names.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function guessPolicyNamesUsing(callable $callback)
    {
        $this->guessPolicyNamesUsingCallback = $callback;

        return $this;
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
     * Resolve the callback for a policy check.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  mixed  $policy
     * @return bool|callable
     */
    protected function resolvePolicyCallback($user, $ability, array $arguments, $policy)
    {
        if (! is_callable([$policy, $this->formatAbilityToMethod($ability)])) {
            return false;
        }

        return function () use ($user, $ability, $arguments, $policy) {
            // This callback will be responsible for calling the policy's before method and
            // running this policy method if necessary. This is used to when objects are
            // mapped to policy objects in the user's configurations or on this class.
            $result = $this->callPolicyBefore(
                $policy, $user, $ability, $arguments
            );

            // When we receive a non-null result from this before method, we will return it
            // as the "final" results. This will allow developers to override the checks
            // in this policy to return the result for all rules defined in the class.
            if (! is_null($result)) {
                return $result;
            }

            $method = $this->formatAbilityToMethod($ability);

            return $this->callPolicyMethod($policy, $method, $user, $arguments);
        };
    }

    /**
     * Call the "before" method on the given policy, if applicable.
     *
     * @param  mixed  $policy
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return mixed
     */
    protected function callPolicyBefore($policy, $user, $ability, $arguments)
    {
        if (! method_exists($policy, 'before')) {
            return null;
        }

        if ($this->canBeCalledWithUser($user, $policy, 'before')) {
            return $policy->before($user, $ability, ...$arguments);
        }
    }

    /**
     * Call the appropriate method on the given policy.
     *
     * @param  mixed  $policy
     * @param  string  $method
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $arguments
     * @return mixed
     */
    protected function callPolicyMethod($policy, $method, $user, array $arguments)
    {
        // If this first argument is a string, that means they are passing a class name
        // to the policy. We will remove the first argument from this argument array
        // because this policy already knows what type of models it can authorize.
        if (isset($arguments[0]) && is_string($arguments[0])) {
            array_shift($arguments);
        }

        if (! is_callable([$policy, $method])) {
            return null;
        }

        if ($this->canBeCalledWithUser($user, $policy, $method)) {
            return $policy->{$method}($user, ...$arguments);
        }
    }

    /**
     * Format the policy ability into a method name.
     *
     * @param  string  $ability
     * @return string
     */
    protected function formatAbilityToMethod($ability)
    {
        return strpos($ability, '-') !== false ? Str::camel($ability) : $ability;
    }

    /**
     * Get a gate instance for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static(
            $this->container, $callback, $this->abilities,
            $this->policies, $this->beforeCallbacks, $this->afterCallbacks,
            $this->guessPolicyNamesUsingCallback
        );
    }

    /**
     * Resolve the user from the user resolver.
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Get all of the defined abilities.
     *
     * @return array
     */
    public function abilities()
    {
        return $this->abilities;
    }

    /**
     * Get all of the defined policies.
     *
     * @return array
     */
    public function policies()
    {
        return $this->policies;
    }
}
