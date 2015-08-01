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
     * @param  string  $callback
     * @param  array  $parameters
     * @return void
     */
    public function __construct($callback, array $parameters = [])
    {
        $this->callback = $callback;
        $this->parameters = $parameters;

        if (! is_string($this->callback) && ! is_callable($this->callback)) {
            throw new InvalidArgumentException(
                'Invalid scheduled callback event. Must be string or callable.'
            );
        }
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
            touch($this->mutexPath());
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
            @unlink($this->mutexPath());
        }
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @return $this
     */
    public function withoutOverlapping()
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
            );
        }

        return $this->skip(function () {
            return file_exists($this->mutexPath());
        });
    }

    /**
     * Get the mutex path for the scheduled command.
     *
     * @return string
     */
    protected function mutexPath()
    {
        return storage_path().'/framework/schedule-'.md5($this->description);
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
