<?php

namespace Illuminate\Auth\Access;

use Closure;
use Exception;
use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;

class Gate implements GateContract
{
    use HandlesAuthorization;

    /**
     * The container instance.
     */
    protected Container $container;

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
    protected array $abilities = [];

    /**
     * All of the defined policies.
     *
     * @var array<string, string>
     */
    protected array $policies = [];

    /**
     * All of the registered before callbacks.
     *
     * @var array<callable>
     */
    protected array $beforeCallbacks = [];

    /**
     * All of the registered after callbacks.
     *
     * @var array<callable>
     */
    protected array $afterCallbacks = [];

    /**
     * All of the defined abilities using class@method notation.
     *
     * @var array<callable>
     */
    protected array $stringCallbacks = [];

    /**
     * The callback to be used to guess policy names.
     *
     * @var callable|null
     */
    protected $guessPolicyNamesUsingCallback;

    /**
     * Create a new gate instance.
     */
    public function __construct(
        Container $container,
        callable $userResolver,
        array $abilities = [],
        array $policies = [],
        array $beforeCallbacks = [],
        array $afterCallbacks = [],
        callable $guessPolicyNamesUsingCallback = null
    ) {
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
     */
    public function has(iterable|string $ability): bool
    {
        $abilities = is_iterable($ability) ? $ability : func_get_args();

        foreach ($abilities as $ability) {
            if (! isset($this->abilities[$ability])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Perform an on-demand authorization check. Throw an authorization exception if the condition or callback is false.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function allowIf(
        Response|Closure|bool $condition,
        string|null $message = null,
        string|null $code = null
    ): Response {
        return $this->authorizeOnDemand($condition, $message, $code, true);
    }

    /**
     * Perform an on-demand authorization check. Throw an authorization exception if the condition or callback is true.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function denyIf(Response|Closure|bool $condition, string|null $message = null, string|null $code = null)
    {
        return $this->authorizeOnDemand($condition, $message, $code, false);
    }

    /**
     * Authorize a given condition or callback.
     *
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorizeOnDemand(
        Response|Closure|bool $condition,
        ?string $message,
        ?string $code,
        bool $allowWhenResponseIs
    ) {
        $user = $this->resolveUser();

        if ($condition instanceof Closure) {
            $response = $this->canBeCalledWithUser($user, $condition)
                ? $condition($user)
                : new Response(false, $message, $code);
        } else {
            $response = $condition;
        }

        return with($response instanceof Response ? $response : new Response(
            (bool) $response === $allowWhenResponseIs, $message, $code
        ))->authorize();
    }

    /**
     * Define a new ability.
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     *
     * @throws \InvalidArgumentException: static
     */
    public function define(string $ability, mixed $callback): static
    {
        if (is_array($callback) && isset($callback[0]) && is_string($callback[0])) {
            $callback = $callback[0].'@'.$callback[1];
        }

        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback)) {
            $this->stringCallbacks[$ability] = $callback;

            $this->abilities[$ability] = $this->buildAbilityCallback($ability, $callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable, callback array, or a 'Class@method' string.");
        }

        return $this;
    }

    /**
     * Define abilities for a resource.
     *
     * @param  array<string, string>|null  $abilities
     */
    public function resource(string $name, string $class, array $abilities = null): static
    {
        $abilities = $abilities ?: [
            'viewAny' => 'viewAny',
            'view' => 'view',
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
     */
    protected function buildAbilityCallback(string $ability, string $callback): Closure
    {
        return function () use ($ability, $callback) {
            if (str_contains($callback, '@')) {
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
     * @param  class-string  $policy
     */
    public function policy(string $class, string $policy): static
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     */
    public function before(callable $callback): static
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     */
    public function after(callable $callback): static
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     */
    public function allows(string $ability, mixed $arguments = []): bool
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     */
    public function denies(string $ability, mixed $arguments = []): bool
    {
        return ! $this->allows($ability, $arguments);
    }

    /**
     * Determine if all of the given abilities should be granted for the current user.
     *
     * @param  array|mixed  $arguments
     */
    public function check(iterable|string $abilities, mixed $arguments = []): bool
    {
        return collect($abilities)->every(
            fn($ability) => $this->inspect($ability, $arguments)->allowed()
        );
    }

    /**
     * Determine if any one of the given abilities should be granted for the current user.
     *
     * @param  array|mixed  $arguments
     */
    public function any(iterable|string $abilities, mixed $arguments = []): bool
    {
        return collect($abilities)->contains(fn($ability) => $this->check($ability, $arguments));
    }

    /**
     * Determine if all of the given abilities should be denied for the current user.
     *
     * @param  array|mixed  $arguments
     */
    public function none(iterable|string $abilities, mixed $arguments = []): bool
    {
        return ! $this->any($abilities, $arguments);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  array|mixed  $arguments
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(string $ability, mixed $arguments = []): Response
    {
        return $this->inspect($ability, $arguments)->authorize();
    }

    /**
     * Inspect the user for the given ability.
     *
     * @param  array|mixed  $arguments
     */
    public function inspect(string $ability, mixed $arguments = []): Response
    {
        try {
            $result = $this->raw($ability, $arguments);

            if ($result instanceof Response) {
                return $result;
            }

            return $result ? Response::allow() : Response::deny();
        } catch (AuthorizationException $e) {
            return $e->toResponse();
        }
    }

    /**
     * Get the raw result from the authorization callback.
     *
     * @param  array|mixed  $arguments
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function raw(string $ability, mixed $arguments = []): mixed
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
        return tap($this->callAfterCallbacks(
            $user, $ability, $arguments, $result
        ), function ($result) use ($user, $ability, $arguments) {
            $this->dispatchGateEvaluatedEvent($user, $ability, $arguments, $result);
        });
    }

    /**
     * Determine whether the callback/method can be called with the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  \Closure|string|array  $class
     * @param  string|null  $method
     * @return bool
     */
    protected function canBeCalledWithUser($user, $class, ?string $method = null): bool
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
     */
    protected function methodAllowsGuests(object|string $class, string $method): bool
    {
        try {
            $reflection = new ReflectionClass($class);

            $method = $reflection->getMethod($method);
        } catch (Exception) {
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
     *
     * @throws \ReflectionException: bool
     */
    protected function callbackAllowsGuests(object|string $callback): bool
    {
        $parameters = (new ReflectionFunction($callback))->getParameters();

        return isset($parameters[0]) && $this->parameterAllowsGuests($parameters[0]);
    }

    /**
     * Determine if the given parameter allows guests.
     */
    protected function parameterAllowsGuests(\ReflectionParameter $parameter): bool
    {
        return ($parameter->hasType() && $parameter->allowsNull()) ||
            ($parameter->isDefaultValueAvailable() && is_null($parameter->getDefaultValue()));
    }

    /**
     * Resolve and call the appropriate authorization callback.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    protected function callAuthCallback($user, string $ability, array $arguments): bool|null|Response
    {
        $callback = $this->resolveAuthCallback($user, $ability, $arguments);

        return $callback($user, ...$arguments);
    }

    /**
     * Call all of the before callbacks and return if a result is given.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    protected function callBeforeCallbacks($user, string $ability, array $arguments): bool|null|Response
    {
        foreach ($this->beforeCallbacks as $before) {
            if (! $this->canBeCalledWithUser($user, $before)) {
                continue;
            }

            if (! is_null($result = $before($user, $ability, $arguments))) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Call all of the after callbacks with check result.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    protected function callAfterCallbacks($user, string $ability, array $arguments, bool|null|Response $result): bool|null|Response
    {
        foreach ($this->afterCallbacks as $after) {
            if (! $this->canBeCalledWithUser($user, $after)) {
                continue;
            }

            $afterResult = $after($user, $ability, $result, $arguments);

            $result ??= $afterResult;
        }

        return $result;
    }

    /**
     * Dispatch a gate evaluation event.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    protected function dispatchGateEvaluatedEvent($user, string $ability, array $arguments, bool|null|Response $result): void
    {
        if ($this->container->bound(Dispatcher::class)) {
            $this->container->make(Dispatcher::class)->dispatch(
                new GateEvaluated($user, $ability, $result, $arguments)
            );
        }
    }

    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    protected function resolveAuthCallback($user, string $ability, array $arguments): callable
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
            //
        };
    }

    /**
     * Get a policy instance for a given class.
     *
     */
    public function getPolicyFor(object|string $class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (! is_string($class)) {
            return null;
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
     * @param  class-string  $class
     */
    protected function guessPolicyName(string $class): array
    {
        if ($this->guessPolicyNamesUsingCallback) {
            return Arr::wrap(call_user_func($this->guessPolicyNamesUsingCallback, $class));
        }

        $classDirname = str_replace('/', '\\', dirname(str_replace('\\', '/', $class)));

        $classDirnameSegments = explode('\\', $classDirname);

        return Arr::wrap(Collection::times(count($classDirnameSegments),
            function ($index) use ($class, $classDirnameSegments) {
                $classDirname = implode('\\', array_slice($classDirnameSegments, 0, $index));

                return $classDirname.'\\Policies\\'.class_basename($class).'Policy';
            })->reverse()->values()->first(function ($class) {
            return class_exists($class);
        }) ?: [$classDirname.'\\Policies\\'.class_basename($class).'Policy']);
    }

    /**
     * Specify a callback to be used to guess policy names.
     *
     */
    public function guessPolicyNamesUsing(callable $callback): static
    {
        $this->guessPolicyNamesUsingCallback = $callback;

        return $this;
    }

    /**
     * Build a policy class instance of the given type.
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function resolvePolicy(object|string $class)
    {
        return $this->container->make($class);
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    protected function resolvePolicyCallback($user, string $ability, array $arguments, mixed $policy): bool|callable
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
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    protected function callPolicyBefore(mixed $policy, $user, string $ability, array $arguments): Response|bool|null
    {
        if (! method_exists($policy, 'before')) {
            return null;
        }

        if ($this->canBeCalledWithUser($user, $policy, 'before')) {
            return $policy->before($user, $ability, ...$arguments);
        }

        return null;
    }

    /**
     * Call the appropriate method on the given policy.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $arguments
     * @return mixed
     */
    protected function callPolicyMethod(mixed $policy, string $method, $user, array $arguments)
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
            return $policy->{$method}($user, ...$arguments)?: null;
        }
    }

    /**
     * Format the policy ability into a method name.
     */
    protected function formatAbilityToMethod(string $ability): string
    {
        return str_contains($ability, '-') ? Str::camel($ability) : $ability;
    }

    /**
     * Get a gate instance for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user): static
    {
        $callback = fn() => $user;

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
     * @return array<string, string>
     */
    public function abilities(): array
    {
        return $this->abilities;
    }

    /**
     * Get all of the defined policies.
     *
     * @return array<string, string>
     */
    public function policies(): array
    {
        return $this->policies;
    }

    /**
     * Set the container instance used by the gate.
     */
    public function setContainer(Container $container): static
    {
        $this->container = $container;

        return $this;
    }
}
