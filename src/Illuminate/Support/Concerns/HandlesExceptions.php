<?php

namespace Illuminate\Support\Concerns;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Psr\Log\LoggerInterface;
use Throwable;

trait HandlesExceptions
{
    /**
     * The exception handler instance.
     *
     * @var \Illuminate\Contracts\Debug\ExceptionHandler|null
     */
    protected $exceptionHandler;

    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface|null
     */
    protected $logger;

    /**
     * Handle an exception with consistent error reporting.
     *
     * @param  \Throwable  $exception
     * @param  array  $context
     * @param  string|null  $level
     * @return void
     */
    protected function handleException(Throwable $exception, array $context = [], ?string $level = null): void
    {
        $this->reportException($exception, $context, $level);
    }

    /**
     * Handle an exception and return a default value on failure.
     *
     * @param  \Throwable  $exception
     * @param  mixed  $default
     * @param  array  $context
     * @param  string|null  $level
     * @return mixed
     */
    protected function handleExceptionWithDefault(Throwable $exception, $default = null, array $context = [], ?string $level = null)
    {
        $this->reportException($exception, $context, $level);
        
        return value($default);
    }

    /**
     * Execute a callback and handle any exceptions that occur.
     *
     * @param  \Closure  $callback
     * @param  mixed  $default
     * @param  array  $context
     * @param  string|null  $level
     * @return mixed
     */
    protected function executeWithExceptionHandling(Closure $callback, $default = null, array $context = [], ?string $level = null)
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            return $this->handleExceptionWithDefault($exception, $default, $context, $level);
        }
    }

    /**
     * Report an exception with consistent context and logging.
     *
     * @param  \Throwable  $exception
     * @param  array  $context
     * @param  string|null  $level
     * @return void
     */
    protected function reportException(Throwable $exception, array $context = [], ?string $level = null): void
    {
        $context = $this->buildExceptionContext($exception, $context);

        // Try to use the application's exception handler first
        if ($this->shouldUseExceptionHandler()) {
            try {
                $this->getExceptionHandler()->report($exception);
                return;
            } catch (Throwable) {
                // Fall back to direct logging if exception handler fails
            }
        }

        // Fall back to direct logging
        $this->logException($exception, $context, $level);
    }

    /**
     * Log an exception directly to the logger.
     *
     * @param  \Throwable  $exception
     * @param  array  $context
     * @param  string|null  $level
     * @return void
     */
    protected function logException(Throwable $exception, array $context = [], ?string $level = null): void
    {
        $logger = $this->getLogger();
        
        if (! $logger) {
            return;
        }

        $level = $level ?? $this->determineLogLevel($exception);
        
        $logger->log($level, $exception->getMessage(), $context);
    }

    /**
     * Build exception context for logging.
     *
     * @param  \Throwable  $exception
     * @param  array  $additionalContext
     * @return array
     */
    protected function buildExceptionContext(Throwable $exception, array $additionalContext = []): array
    {
        $context = [
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        // Add exception-specific context if available
        if (method_exists($exception, 'context')) {
            $context = array_merge($context, $exception->context());
        }

        return array_merge($context, $additionalContext);
    }

    /**
     * Determine the appropriate log level for an exception.
     *
     * @param  \Throwable  $exception
     * @return string
     */
    protected function determineLogLevel(Throwable $exception): string
    {
        // Map common exception types to log levels
        $levelMap = [
            'InvalidArgumentException' => 'warning',
            'LogicException' => 'error',
            'RuntimeException' => 'error',
            'BadMethodCallException' => 'error',
            'OutOfBoundsException' => 'warning',
            'UnexpectedValueException' => 'warning',
        ];

        $exceptionClass = class_basename($exception);
        
        return $levelMap[$exceptionClass] ?? 'error';
    }

    /**
     * Determine if we should use the application's exception handler.
     *
     * @return bool
     */
    protected function shouldUseExceptionHandler(): bool
    {
        return $this->exceptionHandler !== null || Container::getInstance()->bound(ExceptionHandler::class);
    }

    /**
     * Get the exception handler instance.
     *
     * @return \Illuminate\Contracts\Debug\ExceptionHandler|null
     */
    protected function getExceptionHandler(): ?ExceptionHandler
    {
        if ($this->exceptionHandler) {
            return $this->exceptionHandler;
        }

        $container = Container::getInstance();
        
        return $container->bound(ExceptionHandler::class) 
            ? $container->make(ExceptionHandler::class) 
            : null;
    }

    /**
     * Get the logger instance.
     *
     * @return \Psr\Log\LoggerInterface|null
     */
    protected function getLogger(): ?LoggerInterface
    {
        if ($this->logger) {
            return $this->logger;
        }

        $container = Container::getInstance();
        
        return $container->bound(LoggerInterface::class) 
            ? $container->make(LoggerInterface::class) 
            : null;
    }

    /**
     * Set the exception handler instance.
     *
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $handler
     * @return $this
     */
    public function setExceptionHandler(ExceptionHandler $handler)
    {
        $this->exceptionHandler = $handler;
        
        return $this;
    }

    /**
     * Set the logger instance.
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        return $this;
    }
}
