<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\ReportableHandler;
use Illuminate\Support\Arr;
use Illuminate\Testing\Assert;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait InteractsWithExceptionHandling
{
    /**
     * The original exception handler.
     *
     * @var \Illuminate\Contracts\Debug\ExceptionHandler|null
     */
    protected $originalExceptionHandler;

    /**
     * The list of reported exceptions that should be thrown.
     */
    protected $throwReportedExceptions = [];

    /**
     * Restore exception handling.
     *
     * @return $this
     */
    protected function withExceptionHandling()
    {
        if ($this->originalExceptionHandler) {
            $this->app->instance(ExceptionHandler::class, $this->originalExceptionHandler);
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
     * Throw exceptions reported to the exception handler.
     *
     * @param  array<int, class-string<\Throwable>>|string|null  $exceptions
     * @return $this
     */
    protected function throwReportedExceptions(array|string $exceptions = null)
    {
        $exceptions = Arr::wrap($exceptions);

        $this->throwReportedExceptions = array_unique(array_merge(
            $this->throwReportedExceptions,
            $exceptions ?: [Throwable::class],
        ));

        $handler = app(ExceptionHandler::class);
        $throwReportedExceptions = $this->throwReportedExceptions;

        property_exists($handler, 'throwReportedExceptions')
            ? $handler->throwReportedExceptions = $throwReportedExceptions
            : (fn () => $this->reportCallbacks = [
                new ReportableHandler(function (Throwable $e) use ($throwReportedExceptions) {
                    if (collect($throwReportedExceptions)->contains(fn ($class) => $e instanceof $class)) {
                        throw $e;
                    }
                }), ...$this->reportCallbacks,
            ])->call($handler);

        return $this;
    }

    /**
     * Disable exception handling for the test.
     *
     * @param  array  $except
     * @return $this
     */
    protected function withoutExceptionHandling(array $except = [])
    {
        if ($this->originalExceptionHandler == null) {
            $this->originalExceptionHandler = app(ExceptionHandler::class);
        }

        $this->app->instance(ExceptionHandler::class, new class($this->originalExceptionHandler, $except, $this->throwReportedExceptions) implements ExceptionHandler
        {
            public $throwReportedExceptions;

            protected $except;
            protected $originalHandler;

            /**
             * Create a new class instance.
             *
             * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $originalHandler
             * @param  array  $except
             * @param  bool  $throwReportedExceptions
             * @return void
             */
            public function __construct($originalHandler, $except, $throwReportedExceptions)
            {
                $this->except = $except;
                $this->originalHandler = $originalHandler;
                $this->throwReportedExceptions = $throwReportedExceptions;
            }

            /**
             * Report or log an exception.
             *
             * @param  \Throwable  $e
             * @return void
             *
             * @throws \Exception
             */
            public function report(Throwable $e)
            {
                if (collect($this->throwReportedExceptions)->contains(fn ($class) => $e instanceof $class)) {
                    throw $e;
                }
            }

            /**
             * Determine if the exception should be reported.
             *
             * @param  \Throwable  $e
             * @return bool
             */
            public function shouldReport(Throwable $e)
            {
                return false;
            }

            /**
             * Render an exception into an HTTP response.
             *
             * @param  \Illuminate\Http\Request  $request
             * @param  \Throwable  $e
             * @return \Symfony\Component\HttpFoundation\Response
             *
             * @throws \Throwable
             */
            public function render($request, Throwable $e)
            {
                foreach ($this->except as $class) {
                    if ($e instanceof $class) {
                        return $this->originalHandler->render($request, $e);
                    }
                }

                if ($e instanceof NotFoundHttpException) {
                    throw new NotFoundHttpException(
                        "{$request->method()} {$request->url()}", $e, is_int($e->getCode()) ? $e->getCode() : 0
                    );
                }

                throw $e;
            }

            /**
             * Render an exception to the console.
             *
             * @param  \Symfony\Component\Console\Output\OutputInterface  $output
             * @param  \Throwable  $e
             * @return void
             */
            public function renderForConsole($output, Throwable $e)
            {
                (new ConsoleApplication)->renderThrowable($e, $output);
            }
        });

        return $this;
    }

    /**
     * Assert that the given callback throws an exception with the given message when invoked.
     *
     * @param  \Closure  $test
     * @param  class-string<\Throwable>  $expectedClass
     * @param  string|null  $expectedMessage
     * @return $this
     */
    protected function assertThrows(Closure $test, string $expectedClass = Throwable::class, ?string $expectedMessage = null)
    {
        try {
            $test();

            $thrown = false;
        } catch (Throwable $exception) {
            $thrown = $exception instanceof $expectedClass;

            $actualMessage = $exception->getMessage();
        }

        Assert::assertTrue(
            $thrown,
            sprintf('Failed asserting that exception of type "%s" was thrown.', $expectedClass)
        );

        if (isset($expectedMessage)) {
            if (! isset($actualMessage)) {
                Assert::fail(
                    sprintf(
                        'Failed asserting that exception of type "%s" with message "%s" was thrown.',
                        $expectedClass,
                        $expectedMessage
                    )
                );
            } else {
                Assert::assertStringContainsString($expectedMessage, $actualMessage);
            }
        }

        return $this;
    }
}
