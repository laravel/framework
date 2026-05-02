<?php

namespace Illuminate\Routing;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\OptionalAuthenticate;
use Illuminate\Support\Collection;

class SortedMiddleware extends Collection
{
    /**
     * Create a new Sorted Middleware container.
     *
     * @param  array  $priorityMap
     * @param  \Illuminate\Support\Collection|array  $middlewares
     */
    public function __construct(array $priorityMap, $middlewares)
    {
        if ($middlewares instanceof Collection) {
            $middlewares = $middlewares->all();
        }

        $this->items = $this->sortMiddleware($priorityMap, $middlewares);
    }

    /**
     * Sort the middlewares by the given priority map.
     *
     * Each call to this method makes one discrete middleware movement if necessary.
     *
     * @param  array  $priorityMap
     * @param  array  $middlewares
     * @return array
     */
    protected function sortMiddleware($priorityMap, $middlewares)
    {
        $lastIndex = 0;

        foreach ($middlewares as $index => $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            $priorityIndex = $this->priorityMapIndex($priorityMap, $middleware);

            if (! is_null($priorityIndex)) {
                // This middleware is in the priority map. If we have encountered another middleware
                // that was also in the priority map and was at a lower priority than the current
                // middleware, we will move this middleware to be above the previous encounter.
                if (isset($lastPriorityIndex) && $priorityIndex < $lastPriorityIndex) {
                    return $this->sortMiddleware(
                        $priorityMap, array_values($this->moveMiddleware($middlewares, $index, $lastIndex))
                    );
                }

                // This middleware is in the priority map; but, this is the first middleware we have
                // encountered from the map thus far. We'll save its current index plus its index
                // from the priority map so we can compare against them on the next iterations.
                $lastIndex = $index;

                $lastPriorityIndex = $priorityIndex;
            }
        }

        $middlewares = $this->inheritOptionalAuthenticateGuards($middlewares);

        $middlewares = $this->mergeRequiredAuthenticateWithOptional($middlewares);

        return Router::uniqueMiddleware($middlewares);
    }

    /**
     * When optional authentication middleware omits guards, inherit them from the first
     * required Authenticate middleware in the same stack (e.g. auth:sanctum group + optionalAuth).
     *
     * @param  array  $middlewares
     * @return array
     */
    protected function inheritOptionalAuthenticateGuards(array $middlewares): array
    {
        $inheritParams = null;

        foreach ($middlewares as $middleware) {
            $parsed = $this->parseRequiredAuthenticateRouteMiddleware($middleware);

            if ($parsed !== null && $parsed['rawParams'] !== '') {
                $inheritParams = $parsed['rawParams'];

                break;
            }
        }

        if ($inheritParams === null) {
            return $middlewares;
        }

        $result = [];

        foreach ($middlewares as $middleware) {
            $parsed = $this->parseOptionalAuthenticateRouteMiddleware($middleware);

            if ($parsed !== null && $parsed['rawParams'] === '') {
                $result[] = $parsed['class'].':'.$inheritParams;

                continue;
            }

            $result[] = $middleware;
        }

        return $result;
    }

    /**
     * Remove required Authenticate entries when OptionalAuthenticate targets the same guards.
     *
     * @param  array  $middlewares
     * @return array
     */
    protected function mergeRequiredAuthenticateWithOptional(array $middlewares): array
    {
        $optionalKeys = [];

        foreach ($middlewares as $middleware) {
            $parsed = $this->parseOptionalAuthenticateRouteMiddleware($middleware);

            if ($parsed !== null) {
                $optionalKeys[$parsed['key']] = true;
            }
        }

        if ($optionalKeys === []) {
            return $middlewares;
        }

        $result = [];

        foreach ($middlewares as $middleware) {
            $parsed = $this->parseRequiredAuthenticateRouteMiddleware($middleware);

            if ($parsed !== null && isset($optionalKeys[$parsed['key']])) {
                continue;
            }

            $result[] = $middleware;
        }

        return $result;
    }

    /**
     * @param  mixed  $middleware
     * @return array{class: class-string, key: string, rawParams: string}|null
     */
    protected function parseRequiredAuthenticateRouteMiddleware($middleware): ?array
    {
        if (! is_string($middleware)) {
            return null;
        }

        [$class, $parameters] = array_pad(explode(':', $middleware, 2), 2, null);

        if (! class_exists($class) || ($class !== Authenticate::class && ! is_subclass_of($class, Authenticate::class))) {
            return null;
        }

        $rawParams = $parameters ?? '';

        return [
            'class' => $class,
            'rawParams' => $rawParams,
            'key' => $rawParams,
        ];
    }

    /**
     * @param  mixed  $middleware
     * @return array{class: class-string, key: string, rawParams: string}|null
     */
    protected function parseOptionalAuthenticateRouteMiddleware($middleware): ?array
    {
        if (! is_string($middleware)) {
            return null;
        }

        [$class, $parameters] = array_pad(explode(':', $middleware, 2), 2, null);

        if (! class_exists($class) || ($class !== OptionalAuthenticate::class && ! is_subclass_of($class, OptionalAuthenticate::class))) {
            return null;
        }

        $rawParams = $parameters ?? '';

        return [
            'class' => $class,
            'rawParams' => $rawParams,
            'key' => $rawParams,
        ];
    }

    /**
     * Calculate the priority map index of the middleware.
     *
     * @param  array  $priorityMap
     * @param  string  $middleware
     * @return int|null
     */
    protected function priorityMapIndex($priorityMap, $middleware)
    {
        foreach ($this->middlewareNames($middleware) as $name) {
            $priorityIndex = array_search($name, $priorityMap);

            if ($priorityIndex !== false) {
                return $priorityIndex;
            }
        }
    }

    /**
     * Resolve the middleware names to look for in the priority array.
     *
     * @param  string  $middleware
     * @return \Generator
     */
    protected function middlewareNames($middleware)
    {
        $stripped = head(explode(':', $middleware));

        yield $stripped;

        $interfaces = @class_implements($stripped);

        if ($interfaces !== false) {
            foreach ($interfaces as $interface) {
                yield $interface;
            }
        }

        $parents = @class_parents($stripped);

        if ($parents !== false) {
            foreach ($parents as $parent) {
                yield $parent;
            }
        }
    }

    /**
     * Splice a middleware into a new position and remove the old entry.
     *
     * @param  array  $middlewares
     * @param  int  $from
     * @param  int  $to
     * @return array
     */
    protected function moveMiddleware($middlewares, $from, $to)
    {
        array_splice($middlewares, $to, 0, $middlewares[$from]);

        unset($middlewares[$from + 1]);

        return $middlewares;
    }
}
