<?php

namespace Illuminate\Foundation\Configuration;

use Closure;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Arr;

class Exceptions
{
    /**
     * Create a new exception handling configuration instance.
     *
     * @param  \Illuminate\Foundation\Exceptions\Handler  $handler
     * @return void
     */
    public function __construct(public Handler $handler)
    {
    }

    /**
     * Register a reportable callback.
     *
     * @param  callable  $using
     * @return \Illuminate\Foundation\Exceptions\ReportableHandler
     */
    public function report(callable $using)
    {
        return $this->handler->reportable($using);
    }

    /**
     * Register a reportable callback.
     *
     * @param  callable  $reportUsing
     * @return \Illuminate\Foundation\Exceptions\ReportableHandler
     */
    public function reportable(callable $reportUsing)
    {
        return $this->handler->reportable($reportUsing);
    }

    /**
     * Register a renderable callback.
     *
     * @param  callable  $using
     * @return $this
     */
    public function render(callable $using)
    {
        return tap($this, fn () => $this->handler->renderable($using));
    }

    /**
     * Register a renderable callback.
     *
     * @param  callable  $renderUsing
     * @return $this
     */
    public function renderable(callable $renderUsing)
    {
        return tap($this, fn () => $this->handler->renderable($renderUsing));
    }

    /**
     * Register a callback to prepare the final, rendered exception response.
     *
     * @param  callable  $using
     * @return $this
     */
    public function respond(callable $using)
    {
        return tap($this, fn () => $this->handler->respondUsing($using));
    }

    /**
     * Specify the callback that should be used to throttle reportable exceptions.
     *
     * @param  callable  $throttleUsing
     * @return $this
     */
    public function throttle(callable $throttleUsing)
    {
        return tap($this, fn () => $this->handler->throttleUsing($throttleUsing));
    }

    /**
     * Register a new exception mapping.
     *
     * @param  \Closure|string  $from
     * @param  \Closure|string|null  $to
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function map($from, $to = null)
    {
        return tap($this, fn () => $this->handler->map($from, $to));
    }

    /**
     * Set the log level for the given exception type.
     *
     * @param  class-string<\Throwable>  $type
     * @param  \Psr\Log\LogLevel::*  $level
     * @return $this
     */
    public function level(string $type, string $level)
    {
        return tap($this, fn () => $this->handler->level($type, $level));
    }

    /**
     * Register a closure that should be used to build exception context data.
     *
     * @param  \Closure  $contextCallback
     * @return $this
     */
    public function context(Closure $contextCallback)
    {
        return tap($this, fn () => $this->handler->buildContextUsing($contextCallback));
    }

    /**
     * Indicate that the given exception type should not be reported.
     *
     * @param  array|string  $class
     * @return $this
     */
    public function dontReport(array|string $class)
    {
        foreach (Arr::wrap($class) as $exceptionClass) {
            $this->handler->dontReport($exceptionClass);
        }

        return $this;
    }

    /**
     * Do not report duplicate exceptions.
     *
     * @return $this
     */
    public function dontReportDuplicates()
    {
        return tap($this, fn () => $this->handler->dontReportDuplicates());
    }

    /**
     * Indicate that the given attributes should never be flashed to the session on validation errors.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function dontFlash(array|string $attributes)
    {
        return tap($this, fn () => $this->handler->dontFlash($attributes));
    }

    /**
     * Register the callable that determines if the exception handler response should be JSON.
     *
     * @param  callable(\Illuminate\Http\Request $request, \Throwable): bool  $callback
     * @return $this
     */
    public function shouldRenderJsonWhen(callable $callback)
    {
        return tap($this, fn () => $this->handler->shouldRenderJsonWhen($callback));
    }

    /**
     * Indicate that the given exception class should not be ignored.
     *
     * @param  array<int, class-string<\Throwable>>|class-string<\Throwable>  $class
     * @return $this
     */
    public function stopIgnoring(array|string $class)
    {
        return tap($this, fn () => $this->handler->stopIgnoring($class));
    }
}
