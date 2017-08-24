<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait InteractsWithExceptionHandling
{
    /**
     * The previous exception handler.
     *
     * @var ExceptionHandler|null
     */
    protected $previousExceptionHandler;

    /**
     * Restore exception handling.
     *
     * @return $this
     */
    protected function withExceptionHandling()
    {
        if ($this->previousExceptionHandler) {
            $this->app->instance(ExceptionHandler::class, $this->previousExceptionHandler);
        }

        return $this;
    }

    /**
     * Disable exception handling for the test.
     *
     * @return $this
     */
    protected function withoutExceptionHandling()
    {
        return $this->turnOffExceptionHandling();
    }

    /**
     * @param  array  $exceptions
     * @return $this
     */
    protected function handleExceptions(array $exceptions)
    {
        return $this->turnOffExceptionHandling($exceptions);
    }

    protected function handleValidationException()
    {
        return $this->handleExceptions([ValidationException::class]);
    }

    /**
     * Disable exception handling for the test.
     *
     * @param  array  $except
     * @return $this
     */
    protected function turnOffExceptionHandling(array $except = [])
    {
        $this->previousExceptionHandler = app(ExceptionHandler::class);

        $this->app->instance(ExceptionHandler::class, new class($this->previousExceptionHandler, $except) implements ExceptionHandler {
            protected $except;
            protected $previousHandler;

            public function __construct($previousHandler, $except = [])
            {
                $this->previousHandler = $previousHandler;
                $this->except = $except;
            }

            public function report(Exception $e)
            {
            }

            public function render($request, Exception $e)
            {
                if ($e instanceof NotFoundHttpException) {
                    throw new NotFoundHttpException(
                        "{$request->method()} {$request->url()}", null, $e->getCode()
                    );
                }

                foreach ($this->except as $class) {
                    if ($e instanceof $class) {
                        return $this->previousHandler->render($request, $e);
                    }
                }

                throw $e;
            }

            public function renderForConsole($output, Exception $e)
            {
                (new ConsoleApplication)->renderException($e, $output);
            }
        });

        return $this;
    }
}
