<?php

namespace Illuminate\Queue;

class QueueDefaults
{
    /**
     * The mapping of class names to their default queues.
     *
     * @var array<class-string, string>
     */
    protected $defaults = [];

    /**
     * Register the default queue name for the given class.
     *
     * @param  class-string  $class
     * @param  string  $queueName
     * @return $this
     */
    public function set($class, $queueName)
    {
        $this->defaults[$class] = $queueName;

        return $this;
    }

    /**
     * Register default queue names for the given classes.
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
     * Get the default queue name for a given queueable instance.
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
