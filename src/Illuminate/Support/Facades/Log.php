<?php

namespace Illuminate\Support\Facades;

use Psr\Log\LoggerInterface;

/**
 * @method static void emergency(string $message, array $context = array())
 * @method static void alert(string $message, array $context = array())
 * @method static void critical(string $message, array $context = array())
 * @method static void error(string $message, array $context = array())
 * @method static void warning(string $message, array $context = array())
 * @method static void notice(string $message, array $context = array())
 * @method static void info(string $message, array $context = array())
 * @method static void debug(string $message, array $context = array())
 * @method static void log($level, string $message, array $context = array())
 *
 * @see \Illuminate\Log\Logger
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
