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
     * The queues that have been forwarded to another queue and/or connection.
     *
     * @var array<string, array>
     */
    protected $forwards = [];

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

        if (empty($this->forwards)) {
            return null;
        }

        return $this->forwardedConnection(
            $this->getAttributeValue($queueable, QueueAttribute::class, 'queue')
        );
    }

    /**
     * Get the connection the given queue has been forwarded to.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return string|null
     */
    protected function forwardedConnection($queue)
    {
        if (is_null($queue)) {
            return null;
        }

        return $this->forwards[enum_value($queue)][0] ?? null;
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
     * Get the queue the given queue has been forwarded to.
     *
     * @param  string  $queue
     * @param  string|null  $connection
     * @return string
     */
    public function forwardedQueue($queue, $connection = null)
    {
        if (! isset($this->forwards[$queue])) {
            return $queue;
        }

        [$forwardConnection, $forwardQueue] = $this->forwards[$queue];

        return is_null($forwardConnection) || $forwardConnection === $connection
            ? $forwardQueue ?? $queue
            : $queue;
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
     * Register a forward for the given queue.
     *
     * @param  \UnitEnum|array<string, \UnitEnum|string>|string  $queue
     * @param  \UnitEnum|string|null  $to
     * @param  \UnitEnum|string|null  $connection
     * @return void
     */
    public function forward(UnitEnum|array|string $queue, $to = null, $connection = null)
    {
        $forwards = is_array($queue) ? $queue : [enum_value($queue) => $to];

        foreach ($forwards as $from => $destination) {
            $this->forwards[enum_value($from)] = [enum_value($connection), enum_value($destination)];
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
}
