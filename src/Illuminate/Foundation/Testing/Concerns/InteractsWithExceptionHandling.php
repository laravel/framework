<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Exception;
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
        $this->previousExceptionHandler = app(ExceptionHandler::class);

        $this->app->instance(ExceptionHandler::class, new class implements ExceptionHandler {
            public function __construct()
            {
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
