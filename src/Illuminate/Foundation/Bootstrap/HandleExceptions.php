<?php

namespace Illuminate\Foundation\Bootstrap;

use ErrorException;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Support\Env;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\SocketHandler;
use PHPUnit\Runner\ErrorHandler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Throwable;

class HandleExceptions
{
    /**
     * Reserved memory so that errors can be displayed properly on memory exhaustion.
     *
     * @var string|null
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
        static::$reservedMemory = str_repeat('x', 32768);

        static::$app = $app;

        error_reporting(-1);

        set_error_handler($this->forwardsTo('handleError'));

        set_exception_handler($this->forwardsTo('handleException'));

        register_shutdown_function($this->forwardsTo('handleShutdown'));

        if (! $app->environment('testing')) {
            ini_set('display_errors', 'Off');
        }

        if (laravel_cloud()) {
            $this->configureCloudLogging($app);
        }
    }

    /**
     * Report PHP deprecations, or convert PHP errors to ErrorException instances.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0)
    {
        if ($this->isDeprecation($level)) {
            $this->handleDeprecationError($message, $file, $line, $level);
        } elseif (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Reports a deprecation to the "deprecations" logger.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return void
     */
    public function handleDeprecationError($message, $file, $line, $level = E_DEPRECATED)
    {
        if ($this->shouldIgnoreDeprecationErrors()) {
            return;
        }

        try {
            $logger = static::$app->make(LogManager::class);
        } catch (Exception) {
            return;
        }

        $this->ensureDeprecationLoggerIsConfigured();

        $options = static::$app['config']->get('logging.deprecations') ?? [];

        with($logger->channel('deprecations'), function ($log) use ($message, $file, $line, $level, $options) {
            if ($options['trace'] ?? false) {
                $log->warning((string) new ErrorException($message, 0, $level, $file, $line));
            } else {
                $log->warning(sprintf('%s in %s on line %s',
                    $message, $file, $line
                ));
            }
        });
    }

    /**
     * Determine if deprecation errors should be ignored.
     *
     * @return bool
     */
    protected function shouldIgnoreDeprecationErrors()
    {
        return ! class_exists(LogManager::class)
            || ! static::$app->hasBeenBootstrapped()
            || (static::$app->runningUnitTests() && ! Env::get('LOG_DEPRECATIONS_WHILE_TESTING'));
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

            if (is_array($options = $config->get('logging.deprecations'))) {
                $driver = $options['channel'] ?? 'null';
            } else {
                $driver = $options ?? 'null';
            }

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
        static::$reservedMemory = null;

        try {
            $this->getExceptionHandler()->report($e);
        } catch (Exception) {
            $exceptionHandlerFailed = true;
        }

        if (static::$app->runningInConsole()) {
            $this->renderForConsole($e);

            if ($exceptionHandlerFailed ?? false) {
                exit(1);
            }
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
        static::$reservedMemory = null;

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
     * Configure the Laravel Cloud log channels.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected function configureCloudLogging(Application $app)
    {
        $app['config']->set('logging.channels.stderr.formatter_with', [
            'includeStacktraces' => true,
        ]);

        $app['config']->set('logging.channels.laravel-cloud-socket', [
            'driver' => 'monolog',
            'handler' => SocketHandler::class,
            'formatter' => JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => true,
            ],
            'with' => [
                'connectionString' => $_ENV['LARAVEL_CLOUD_LOG_SOCKET'] ??
                                      $_SERVER['LARAVEL_CLOUD_LOG_SOCKET'] ??
                                      'unix:///tmp/cloud-init.sock',
                'persistent' => true,
            ],
        ]);
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
     *
     * @deprecated This method will be removed in a future Laravel version.
     */
    public static function forgetApp()
    {
        static::$app = null;
    }

    /**
     * Flush the bootstrapper's global state.
     *
     * @return void
     */
    public static function flushState()
    {
        if (is_null(static::$app)) {
            return;
        }

        static::flushHandlersState();

        static::$app = null;

        static::$reservedMemory = null;
    }

    /**
     * Flush the bootstrapper's global handlers state.
     *
     * @return void
     */
    public static function flushHandlersState()
    {
        while (true) {
            $previousHandler = set_exception_handler(static fn () => null);

            restore_exception_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_exception_handler();
        }

        while (true) {
            $previousHandler = set_error_handler(static fn () => null);

            restore_error_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_error_handler();
        }

        if (class_exists(ErrorHandler::class)) {
            $instance = ErrorHandler::instance();

            if ((fn () => $this->enabled ?? false)->call($instance)) {
                $instance->disable();
                $instance->enable();
            }
        }
    }
}
