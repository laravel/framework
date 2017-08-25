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
     * Only handle the given exceptions via the exception handler.
     *
     * @param  array  $exceptions
     * @return $this
     */
    protected function handleExceptions(array $exceptions)
    {
        return $this->withoutExceptionHandling($exceptions);
    }

    /**
     * Only handle validation exceptions via the exception handler.
     *
     * @return $this
     */
    protected function handleValidationExceptions()
    {
        return $this->handleExceptions([ValidationException::class]);
    }

    /**
     * Disable exception handling for the test.
     *
     * @return $this
     */
    protected function withoutExceptionHandling(array $except = [])
    {
        $this->previousExceptionHandler = app(ExceptionHandler::class);

        $this->app->instance(ExceptionHandler::class, new class($this->previousExceptionHandler, $except) implements ExceptionHandler {
            protected $except;
            protected $previousHandler;

            /**
             * Create a new class instance.
             *
             * @param \Illuminate\Contracts\Debug\ExceptionHandler
             * @param  array  $except
             * @return void
             */
            public function __construct($previousHandler, $except = [])
            {
                $this->except = $except;
                $this->previousHandler = $previousHandler;
            }

            /**
             * Report the given exception.
             *
             * @param  \Exception  $e
             * @return void
             */
            public function report(Exception $e)
            {
                //
            }

            /**
             * Render the given exception.
             *
             * @param  \Illuminate\Http\Request  $request
             * @param  \Exception  $e
             * @return mixed
             */
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

            /**
             * Render the exception for the console.
             *
             * @param  \Symfony\Component\Console\Output\OutputInterface
             * @param  \Exception  $e
             * @return void
             */
            public function renderForConsole($output, Exception $e)
            {
                (new ConsoleApplication)->renderException($e, $output);
            }
        });

        return $this;
    }
}
