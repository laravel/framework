<?php

namespace Illuminate\Queue;

class QueueDefaults
{
    /**
     * The mapping of class names to their default queues.
     *
     * @var array
     */
    protected $defaults = [];

    /**
     * Set the default queues for the given classes.
     *
     * @param  class-string  $class
     * @param  string  $queue
     * @return $this
     */
    public function set($class, $queue)
    {
        $this->defaults[$class] = $queue;

        return $this;
    }

    /**
     * Set multiple default queues at once.
     *
     * @param  array<class-string, string>  $defaults
     * @return $this
     */
    public function setMany(array $defaults)
    {
        $this->defaults = array_merge($this->defaults, $defaults);

        return $this;
    }

    /**
     * Get the default queue for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function get($queueable)
    {
        if (empty($this->defaults)) {
            return null;
        }

        $classes = array_merge(
            [get_class($queueable)],
            class_parents($queueable) ?: [],
            class_implements($queueable) ?: [],
            class_uses_recursive($queueable)
        );

        foreach ($classes as $class) {
            if (isset($this->defaults[$class])) {
                return $this->defaults[$class];
            }
        }

        return null;
    }

    /**
     * Get all registered default queues.
     *
     * @return array
     */
    public function all()
    {
        return $this->defaults;
    }
}
