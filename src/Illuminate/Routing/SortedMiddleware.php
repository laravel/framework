<?php

namespace Illuminate\Routing;

use Illuminate\Support\Collection;

class SortedMiddleware extends Collection
{
    /**
     * Create a new Sorted Middleware container.
     *
     * @param  array  $priorityMap
     * @param  array|Collection  $middlewares
     * @return void
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
        $hasReverse = false;
        $priorityMap = array_flip($priorityMap);
        $prioritySortMap = [];
        $originalIndexes = [];

        foreach ($middlewares as $index => $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            $stripped = head(explode(':', $middleware));

            if (isset($priorityMap[$stripped])) {
                $priorityIndex = $priorityMap[$stripped];
                $prioritySortMap[$priorityIndex][] = $middleware;
                $originalIndexes[] = $index;

                // This middleware is in the priority map. If we have encountered another middleware
                // that was also in the priority map and was at a lower priority than the current
                // middleware, we will need sorting.
                if (isset($lastPriorityIndex) && $priorityIndex < $lastPriorityIndex) {
                    $hasReverse = true;
                }

                // This middleware is in the priority map; but, this is the first middleware we have
                // encountered from the map thus far. We'll save its current index plus its index
                // from the priority map so we can compare against them on the next iterations.
                $lastPriorityIndex = $priorityIndex;
            }
        }

        if (! $hasReverse) {
            return array_values(array_unique($middlewares, SORT_REGULAR));
        }

        // Sorting
        ksort($prioritySortMap);

        // Put middleware to the right place
        $i = 0;
        foreach ($prioritySortMap as $priorityMiddlewares) {
            foreach ($priorityMiddlewares as $middleware) {
                $middlewares[$originalIndexes[$i++]] = $middleware;
            }
        }

        return array_values(array_unique($middlewares, SORT_REGULAR));
    }
}
