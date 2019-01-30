<?php

namespace Illuminate\Console\Scheduling;

use LogicException;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;

class CallbackEvent extends Event
{
    /**
     * The callback to call.
     *
     * @var string
     */
    protected $callback;

    /**
     * The parameters to pass to the method.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Console\Scheduling\EventMutex  $mutex
     * @param  string  $callback
     * @param  array  $parameters
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(EventMutex $mutex, $callback, array $parameters = [])
    {
        if (! is_string($callback) && ! is_callable($callback)) {
            throw new InvalidArgumentException(
                'Invalid scheduled callback event. Must be a string or callable.'
            );
        }

        $this->mutex = $mutex;
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
        if ($this->description && $this->withoutOverlapping &&
            ! $this->mutex->create($this)) {
            return;
        }

        $pid = getmypid();

        register_shutdown_function(function () use ($pid) {
            if ($pid === getmypid()) {
                $this->removeMutex();
            }
        });

        parent::callBeforeCallbacks($container);

        try {
            $response = is_object($this->callback)
                        ? $container->call([$this->callback, '__invoke'], $this->parameters)
                        : $container->call($this->callback, $this->parameters);
        } finally {
            $this->removeMutex();

            parent::callAfterCallbacks($container);
        }

        return $response;
    }

    /**
     * Clear the mutex for the event.
     *
     * @return void
     */
    protected function removeMutex()
    {
        if ($this->description) {
            $this->mutex->forget($this);
        }
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @param  int  $expiresAt
     * @return $this
     *
     * @throws \LogicException
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
            );
        }

        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->skip(function () {
            return $this->mutex->exists($this);
        });
    }

    /**
     * Allow the event to only run on one server for each cron expression.
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function onOneServer()
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to only run on one server. Use the 'name' method before 'onOneServer'."
            );
        }

        $this->onOneServer = true;

        return $this;
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * @return string
     */
    public function mutexName()
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
