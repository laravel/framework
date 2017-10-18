<?php

namespace Illuminate\Support\Facades;

use Psr\Log\LoggerInterface;

/**
 * @see \Illuminate\Log\Writer
 *
 * @method static boolean debug($message, array $context = array())
 * @method static boolean info($message, array $context = array())
 * @method static boolean notice($message, array $context = array())
 * @method static boolean warning($message, array $context = array())
 * @method static boolean error($message, array $context = array())
 * @method static boolean critical($message, array $context = array())
 * @method static boolean alert($message, array $context = array())
 * @method static boolean emergency($message, array $context = array())
 * @method static void log($level, $message, array $context = array())
 * @method static void write($level, $message, array $context = array())
 * @method static void useFiles($path, $level = 'debug')S
 * @method static void useDailyFiles($path, $days = 0, $level = 'debug')
 * @method static \Psr\Log\LoggerInterface useSyslog($name = 'laravel', $level = 'debug', $facility = 8)
 * @method static void useErrorLog($level = 'debug', $messageType = 0)
 * @method static void listen(Closure $callback)
 * @method static \Monolog\Logger getMonolog()
 * @method static \Illuminate\Contracts\Events\Dispatcher getEventDispatcher()
 * @method static void setEventDispatcher($dispatcher)
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
