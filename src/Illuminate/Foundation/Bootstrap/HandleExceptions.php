<?php

namespace Illuminate\Foundation\Bootstrap;

use ErrorException;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use Monolog\Handler\NullHandler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Throwable;

class HandleExceptions
{
    /**
     * Reserved memory so that errors can be displayed properly on memory exhaustion.
     *
     * @var string
     */
    public static $reservedMemory;

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected static $app;

    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        self::$reservedMemory = str_repeat('x', 10240);

        static::$app = $app;

        error_reporting(-1);

        set_error_handler($this->forwardsTo('handleError'));

        set_exception_handler($this->forwardsTo('handleException'));

        register_shutdown_function($this->forwardsTo('handleShutdown'));

        if (! $app->environment('testing')) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Report PHP deprecations, or convert PHP errors to ErrorException instances.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  array  $context
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if ($this->isDeprecation($level)) {
            return $this->handleDeprecation($message, $file, $line);
        }

        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Reports a deprecation to the "deprecations" logger.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @return void
     */
    public function handleDeprecation($message, $file, $line)
    {
        if (! class_exists(LogManager::class)
            || ! static::$app->hasBeenBootstrapped()
            || static::$app->runningUnitTests()
        ) {
            return;
        }

        try {
            $logger = static::$app->make(LogManager::class);
        } catch (Exception $e) {
            return;
        }

        $this->ensureDeprecationLoggerIsConfigured();

        with($logger->channel('deprecations'), function ($log) use ($message, $file, $line) {
            $log->warning(sprintf('%s in %s on line %s',
                $message, $file, $line
            ));
        });
    }

    /**
     * Ensure the "deprecations" logger is configured.
     *
     * @return void
     */
    protected function ensureDeprecationLoggerIsConfigured()
    {
        with(static::$app['config'], function ($config) {
            if ($config->get('logging.channels.deprecations')) {
                return;
            }

            $this->ensureNullLogDriverIsConfigured();

            $driver = $config->get('logging.deprecations') ?? 'null';

            $config->set('logging.channels.deprecations', $config->get("logging.channels.{$driver}"));
        });
    }

    /**
     * Ensure the "null" log driver is configured.
     *
     * @return void
     */
    protected function ensureNullLogDriverIsConfigured()
    {
        with(static::$app['config'], function ($config) {
            if ($config->get('logging.channels.null')) {
                return;
            }

            $config->set('logging.channels.null', [
                'driver' => 'monolog',
                'handler' => NullHandler::class,
            ]);
        });
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function handleException(Throwable $e)
    {
        try {
            self::$reservedMemory = null;

            $this->getExceptionHandler()->report($e);
        } catch (Exception $e) {
            //
        }

        if (static::$app->runningInConsole()) {
            $this->renderForConsole($e);
        } else {
            $this->renderHttpResponse($e);
        }
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderForConsole(Throwable $e)
    {
        $this->getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderHttpResponse(Throwable $e)
    {
        $this->getExceptionHandler()->render(static::$app['request'], $e)->send();
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown()
    {
        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalErrorFromPhpError($error, 0));
        }
    }

    /**
     * Create a new fatal error instance from an error array.
     *
     * @param  array  $error
     * @param  int|null  $traceOffset
     * @return \Symfony\Component\ErrorHandler\Error\FatalError
     */
    protected function fatalErrorFromPhpError(array $error, $traceOffset = null)
    {
        return new FatalError($error['message'], 0, $error, $traceOffset);
    }

    /**
     * Forward a method call to the given method if an application instance exists.
     *
     * @return callable
     */
    protected function forwardsTo($method)
    {
        return fn (...$arguments) => static::$app
            ? $this->{$method}(...$arguments)
            : false;
    }

    /**
     * Determine if the error level is a deprecation.
     *
     * @param  int  $level
     * @return bool
     */
    protected function isDeprecation($level)
    {
        return in_array($level, [E_DEPRECATED, E_USER_DEPRECATED]);
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int  $type
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * Get an instance of the exception handler.
     *
     * @return \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected function getExceptionHandler()
    {
        return static::$app->make(ExceptionHandler::class);
    }

    /**
     * Clear the local application instance from memory.
     *
     * @return void
     */
    public static function forgetApp()
    {
        static::$app = null;
    }
}
