<?php

namespace Illuminate\Console\Scheduling;

use LogicException;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;

class CallbackEvent extends Event
{
    /**
     * The callback to call.
     *
     * @var string
     */
    protected $callback;

    /**
     * The cache store implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The parameters to pass to the method.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Create a new event instance.
     *
     * @param  string  $callback
     * @param  array  $parameters
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($callback, array $parameters = [], Cache $cache)
    {
        if (! is_string($callback) && ! is_callable($callback)) {
            throw new InvalidArgumentException(
                'Invalid scheduled callback event. Must be string or callable.'
            );
        }

        $this->cache = $cache;
        $this->callback = $callback;
        $this->parameters = $parameters;
    }

    /**
     * Run the given event.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     *
     * @throws \Exception
     */
    public function run(Container $container)
    {
        if ($this->description) {
            $this->cache->put($this->mutexName(), true, 1440);
        }

        try {
            $response = $container->call($this->callback, $this->parameters);
        } finally {
            $this->removeMutex();
        }

        parent::callAfterCallbacks($container);

        return $response;
    }

    /**
     * Remove the mutex file from disk.
     *
     * @return void
     */
    protected function removeMutex()
    {
        if ($this->description) {
            $this->cache->forget($this->mutexName());
        }
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function withoutOverlapping()
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
            );
        }

        return $this->skip(function () {
            return $this->cache->has($this->mutexName());
        });
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * @return string
     */
    protected function mutexName()
    {
        return 'framework/schedule-'.sha1($this->description);
    }

    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return is_string($this->callback) ? $this->callback : 'Closure';
    }
}
