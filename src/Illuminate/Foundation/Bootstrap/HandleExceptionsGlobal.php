<?php

namespace Illuminate\Foundation\Bootstrap;

use Throwable;

class HandleExceptionsGlobal
{
    protected static $instance;

    /**
     * @var \Illuminate\Foundation\Bootstrap\HandleExceptions
     */
    protected $handler;

    protected function __construct()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Register a global exception handler.
     *
     * @param  \Illuminate\Foundation\Bootstrap\HandleExceptions  $handler
     * @return void
     */
    public static function register(HandleExceptions $handler)
    {
        if (! self::$instance) {
            self::$instance = new self;
        }

        self::$instance->handler = $handler;
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
        $this->handler->handleError($level, $message, $file, $line, $context);
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
        $this->handler->handleException($e);
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown()
    {
        $this->handler->handleShutdown();
    }
}
