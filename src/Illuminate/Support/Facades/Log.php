<?php

namespace Illuminate\Support\Facades;

use Psr\Log\LoggerInterface;

/**
 * @see \Illuminate\Log\Writer
 *
 * @method static boolean info($message, $context = array()) Adds a log record at the INFO level.
 * @method static boolean notice($message, $context = array()) Adds a log record at the NOTICE level.
 * @method static boolean warning($message, $context = array()) Adds a log record at the WARNING level.
 * @method static boolean error($message, $context = array()) Adds a log record at the ERROR level.
 * @method static boolean critical($message, $context = array()) Adds a log record at the CRITICAL level.
 * @method static boolean alert($message, $context = array()) Adds a log record at the ALERT level.
 * @method static boolean eme rgency($message, $context = array()) Adds a log record at the EMERGENCY level.
 * @method static void log($level, $message, $context = array()) Log a message to the logs.
 * @method static void write($level, $message, $context = array()) Dynamically pass log calls into the writer
 * @method static void useFiles($path, $level = 'debug') Register a file log handler.
 * @method static void useDailyFiles($path, $days = 0, $level = 'debug') Register a daily file log handler.
 * @method static \Psr\Log\LoggerInterface useSyslog($name = 'laravel', $level = 'debug', $facility = 8) Register a Syslog handler.
 * @method static void useErrorLog($level = 'debug', $messageType = 0) Register an error_log handler.
 * @method static void listen($callback) Register a new callback handler for when a log event is triggered.
 * @method static \Monolog\Logger getMonolog() Get the underlying Monolog instance.
 * @method static \Illuminate\Contracts\Events\Dispatcher getEventDispatcher() Get the event dispatcher instance.
 * @method static void setEventDispatcher($dispatcher) Set the event dispatcher instance.
 */
class Log extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LoggerInterface::class;
    }
}
