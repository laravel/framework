<?php

namespace Illuminate\Foundation\Exceptions;

use Illuminate\Support\Traits\ReflectsClosures;
use Throwable;

class ReportableHandler
{
    use ReflectsClosures;

    /**
     * The underlying callback.
     *
     * @var callable
     */
    protected $callback;

    /**
     * Indicates if reporting should stop after invoking this handler.
     *
     * @var bool
     */
    protected $shouldStop = false;

    /**
     * Create a new reportable handler instance.
     *
     * @param  callable  $callback
     * @return void
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Invoke the handler.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function __invoke(Throwable $e)
    {
        $result = call_user_func($this->callback, $e);

        if ($result === false) {
            return false;
        }

        return ! $this->shouldStop;
    }

    /**
     * Determine if the callback handles the given exception.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function handles(Throwable $e)
    {
        return is_a($e, $this->firstClosureParameterType($this->callback));
    }

    /**
     * Indicate that report handling should stop after invoking this callback.
     *
     * @return $this
     */
    public function stop()
    {
        $this->shouldStop = true;

        return $this;
    }
}
