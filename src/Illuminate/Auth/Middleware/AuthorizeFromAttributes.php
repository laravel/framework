<?php

namespace Illuminate\Auth\Middleware;

use Closure;
use Illuminate\Auth\Attributes\Authorize as AuthorizeAttribute;
use Illuminate\Auth\Attributes\Gate as GateAttribute;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

use function Illuminate\Support\enum_value;

class AuthorizeFromAttributes
{
    /**
     * The gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();
        
        if (! $route) {
            return $next($request);
        }

        $controller = $route->getController();
        $action = $route->getActionMethod();

        if (! $controller || ! $action) {
            return $next($request);
        }

        $this->authorizeFromAttributes($request, $controller, $action);

        return $next($request);
    }

    /**
     * Authorize based on controller and method attributes.
     */
    protected function authorizeFromAttributes($request, $controller, string $action): void
    {
        $reflection = new ReflectionClass($controller);

        // Process class-level authorization attributes
        $this->processClassAttributes($request, $reflection);

        // Process method-level authorization attributes
        if ($reflection->hasMethod($action)) {
            $method = $reflection->getMethod($action);
            $this->processMethodAttributes($request, $method);
        }
    }

    /**
     * Process class-level authorization attributes.
     */
    protected function processClassAttributes($request, ReflectionClass $reflection): void
    {
        // Process Authorize attributes
        $authorizeAttributes = $reflection->getAttributes(AuthorizeAttribute::class);
        foreach ($authorizeAttributes as $attribute) {
            $attr = $attribute->newInstance();
            $this->gate->authorize($attr->ability, $this->resolveArguments($request, $attr->arguments));
        }

        // Process Gate attributes
        $gateAttributes = $reflection->getAttributes(GateAttribute::class);
        foreach ($gateAttributes as $attribute) {
            $attr = $attribute->newInstance();
            $this->gate->authorize($attr->ability, $this->resolveArguments($request, $attr->arguments));
        }
    }

    /**
     * Process method-level authorization attributes.
     */
    protected function processMethodAttributes($request, ReflectionMethod $method): void
    {
        // Process Authorize attributes
        $authorizeAttributes = $method->getAttributes(AuthorizeAttribute::class);
        foreach ($authorizeAttributes as $attribute) {
            $attr = $attribute->newInstance();
            $this->gate->authorize($attr->ability, $this->resolveArguments($request, $attr->arguments));
        }

        // Process Gate attributes
        $gateAttributes = $method->getAttributes(GateAttribute::class);
        foreach ($gateAttributes as $attribute) {
            $attr = $attribute->newInstance();
            $this->gate->authorize($attr->ability, $this->resolveArguments($request, $attr->arguments));
        }
    }

    /**
     * Resolve attribute arguments from request context.
     */
    protected function resolveArguments($request, mixed $arguments): array
    {
        if (empty($arguments)) {
            return [];
        }

        if (! is_array($arguments)) {
            $arguments = [$arguments];
        }

        return (new Collection($arguments))
            ->map(fn ($argument) => $this->resolveArgument($request, $argument))
            ->all();
    }

    /**
     * Resolve a single argument from request context.
     */
    protected function resolveArgument($request, mixed $argument): mixed
    {
        // If it's already a model instance, return as-is
        if ($argument instanceof Model) {
            return $argument;
        }

        // If it's a string, try to resolve from route parameters
        if (is_string($argument)) {
            // Check if it's a route parameter
            $routeParam = $request->route($argument);
            if ($routeParam !== null) {
                return $routeParam;
            }

            // Check if it's a quoted string literal
            if (preg_match("/^['\"](.*)['\"]$/", trim($argument), $matches)) {
                return $matches[1];
            }

            // If it looks like a class name, return as-is for policy resolution
            if ($this->isClassName($argument)) {
                return $argument;
            }

            // Otherwise, try to get from route parameters
            return $request->route($argument, $argument);
        }

        return $argument;
    }

    /**
     * Checks if the given string looks like a fully qualified class name.
     */
    protected function isClassName(string $value): bool
    {
        return str_contains($value, '\\');
    }
}
