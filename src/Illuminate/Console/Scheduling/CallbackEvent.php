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
     * @param  \Illuminate\Console\Scheduling\Mutex  $mutex
     * @param  string  $callback
     * @param  array  $parameters
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Mutex $mutex, $callback, array $parameters = [])
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
     * Run the given event in the foreground.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     */
    public function runForegroundProcess(Container $container)
    {
        if ($this->outputNotBeingCaptured()) {
            return $container->call($this->callback, $this->parameters);
        }

        ob_start();
        $container->call($this->callback, $this->parameters);
        $output = $this->storeOutput(ob_get_contents());
        ob_end_flush();

        return $output;
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @param  int  $expiresAt
     * @return $this
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        if (! $this->description) {
            throw new LogicException(
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
            );
        }

        return parent::withoutOverlapping($expiresAt);
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * @return string
     */
    public function mutexName()
    {
        return 'schedule-mutex-'.sha1($this->expression.$this->description);
    }

    /**
     * Do not allow the event to run in background without a name.
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function runInBackground()
    {
        if (! $this->description) {
            throw new LogicException(
                "A scheduled event name is required to run in the background. Use the 'name' method before 'runInBackground'."
            );
        }

        return parent::runInBackground();
    }

    /**
     * Store any captured output.
     *
     * @param  string  $output
     * @return string
     */
    public function storeOutput($output)
    {
        file_put_contents($this->output, $output.PHP_EOL, $this->shouldAppendOutput ? FILE_APPEND : null);

        return $output;
    }
}
