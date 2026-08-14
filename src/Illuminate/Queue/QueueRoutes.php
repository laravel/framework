<?php

namespace Illuminate\Queue;

use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use Illuminate\Support\Traits\ReadsClassAttributes;
use UnitEnum;

use function Illuminate\Support\enum_value;

class QueueRoutes
{
    use ReadsClassAttributes;

    /**
     * The mapping of class names to their default routes.
     *
     * @var array<class-string, array|string>
     */
    protected $routes = [];

    /**
     * The mapping of queue names to their routes.
     *
     * @var array<string, array{0: string|null, 1: string|null}>
     */
    protected $queueRoutes = [];

    /**
     * Get the queue connection that a given queueable instance should be routed to.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function getConnection($queueable)
    {
        $route = $this->getRoute($queueable);

        if (! is_null($route)) {
            return is_string($route) ? null : $route[0];
        }

        if (empty($this->queueRoutes)) {
            return null;
        }

        $queue = $this->getAttributeValue($queueable, QueueAttribute::class, 'queue');

        return is_null($queue) ? null : ($this->queueRoutes[enum_value($queue)][0] ?? null);
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
     * Get the route for a given queueable instance.
     *
     * @param  object  $queueable
     * @return array|string|null
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
     * @param  \UnitEnum|string|null  $queue
     * @param  \UnitEnum|string|null  $connection
     * @return void
     */
    public function set(array|string $class, $queue = null, $connection = null)
    {
        $routes = is_array($class) ? $class : [$class => [$connection, $queue]];

        foreach ($routes as $from => $to) {
            $this->routes[$from] = is_array($to)
                ? array_map(enum_value(...), $to)
                : enum_value($to);
        }
    }

    /**
     * Get all registered queue routes.
     *
     * @return array<class-string, array|string>
     */
    public function all()
    {
        return $this->routes;
    }

    /**
     * Register the queue route for the given queue name.
     *
     * @param  array<string, \UnitEnum|string>|\UnitEnum|string  $queue
     * @param  \UnitEnum|string|null  $to
     * @param  \UnitEnum|string|null  $connection
     * @return void
     */
    public function setQueue(array|string|UnitEnum $queue, $to = null, $connection = null)
    {
        $routes = is_array($queue) ? $queue : [enum_value($queue) => $to];

        foreach ($routes as $from => $destination) {
            $this->queueRoutes[enum_value($from)] = [enum_value($connection), enum_value($destination)];
        }
    }

    /**
     * Get the routed name for the given queue and connection.
     *
     * @param  string  $queue
     * @param  string|null  $connection
     * @return string
     */
    public function resolveQueue($queue, $connection = null)
    {
        if (! isset($this->queueRoutes[$queue])) {
            return $queue;
        }

        [$routeConnection, $routeQueue] = $this->queueRoutes[$queue];

        return is_null($routeConnection) || $routeConnection === $connection
            ? $routeQueue ?? $queue
            : $queue;
    }
}
