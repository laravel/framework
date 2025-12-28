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
     * Register the default queue route for the given class.
     *
     * @param  class-string  $class
     * @param  string  $queueName
     * @return void
     */
    public function set($class, $queueName)
    {
        $this->routes[$class] = $queueName;
    }

    /**
     * Register the default queue routes for the given classes.
     *
     * @param  array<class-string, string>  $defaults
     * @return void
     */
    public function setMany(array $defaults)
    {
        $this->routes = array_merge($this->routes, $defaults);
    }

    /**
     * Get the default queue route for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function get($queueable)
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
     * Get all registered queue routes.
     *
     * @return array
     */
    public function all()
    {
        return $this->routes;
    }
}
