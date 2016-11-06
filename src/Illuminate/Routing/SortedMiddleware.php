<?php

namespace Illuminate\Routing;

use Illuminate\Support\Collection;

class SortedMiddleware extends Collection
{
    /**
     * Sort the middlewares by the given priority map.
     *
     * Each call to this method makes one discrete middleware movement if necessary.
     *
     * @param  array  $priorityMap
     * @return array
     */
    public function sortMiddleware(array $priorityMap)
    {
        // First we need to make sure the middleware are unique.
        // Then we can group them by their parsed name. We'll
        // keep the closure middleware in separate groups.
        $groupedMiddleware = $this->unique()
            ->groupBy(function ($middleware, $index) {
                if (! is_string($middleware)) {
                    return $index;
                }

                return head(explode(':', $middleware));
            }, true);

        $sortedGroups = $this->sortGroups($priorityMap, $groupedMiddleware->keys());

        // Now we can reorder the grouped middleware according to the
        // order of the sorted groups. We need to search the index
        // of each sorted middleware and sort by that position.
        $middleware = $groupedMiddleware->sortBy(function ($group, $name) use ($sortedGroups) {
            return $sortedGroups->search($name);
        });

        return $middleware->flatten();
    }

    /**
     * Sort the middleware groups against a priority map.
     *
     * @param  array                          $priorityMap
     * @param  \Illuminate\Support\Collection $middlewares
     * @return \Illuminate\Support\Collection
     */
    protected function sortGroups(array $priorityMap, Collection $middlewares)
    {
        $lastIndex = 0;

        return collect($priorityMap)
            ->intersect($middlewares)
            ->reduce(function ($middlewares, $middleware) use (&$lastIndex) {
                $index = $middlewares->search($middleware);

                // We have encountered a middleware that is at a lower index than the previous encounter,
                // so we will move the previous encounter just before the current position. Then we'll
                // increment the current position because it has been shifted by the previous item.
                if ($index < $lastIndex) {
                    $middlewares = $this->moveMiddleware($middlewares, $lastIndex, $index++);
                }

                $lastIndex = $index;

                return $middlewares;
            }, $middlewares);
    }

    /**
     * Splice a middleware into a new position and remove the old entry.
     *
     * @param  \Illuminate\Support\Collection $middlewares
     * @param  int                            $from
     * @param  int                            $to
     * @return \Illuminate\Support\Collection
     */
    protected function moveMiddleware(Collection $middlewares, $from, $to)
    {
        $item = $middlewares->pull($from);

        $middlewares->splice($to, 0, $item);

        return $middlewares->values();
    }
}
