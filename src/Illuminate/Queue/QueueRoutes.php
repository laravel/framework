<?php

namespace Illuminate\Queue;

class QueueRoutes
{
    /**
     * The mapping of class names to their default routes.
     *
     * @var array<class-string, string>
     */
    protected $routes = [];

    /**
     * Get the queue connection that a given queueable instance should be routed to.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function getConnection($queueable)
    {
        $route = $this->getRoute($queueable);

        if (is_null($route)) {
            return;
        }

        return is_string($route)
            ? $route
            : $route[0];
    }

    /**
     * Get the queue that a given queueable instance should be routed to.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function getQueue($queueable)
    {
        $route = $this->getRoute($queueable);

        if (is_null($route)) {
            return;
        }

        return is_string($route)
            ? $route
            : $route[1];
    }

    /**
     * Get the queue that a given queueable instance should be routed to.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function getRoute($queueable)
    {
        if (empty($this->routes)) {
            return null;
        }

        $classes = array_merge(
            [get_class($queueable)],
            class_parents($queueable) ?: [],
            class_implements($queueable) ?: [],
            class_uses_recursive($queueable)
        );

        foreach ($classes as $class) {
            if (isset($this->routes[$class])) {
                return $this->routes[$class];
            }
        }

        return null;
    }

    /**
     * Register the queue route for the given class.
     *
     * @param  array|class-string  $class
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return void
     */
    public function set(array|string $class, $queue = null, $connection = null)
    {
        $routes = is_array($class) ? $class : [$class => [$connection, $queue]];

        foreach ($routes as $from => $to) {
            $this->routes[$from] = $to;
        }
    }

    /**
     * Get all registered queue routes.
     *
     * @return array
     */
    public function all()
    {
        return $this->routes;
    }
}
