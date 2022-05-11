<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Psr\Log\LoggerInterface channel(string $channel = null)
 * @method static \Psr\Log\LoggerInterface stack(array $channels, string $channel = null)
 * @method static \Psr\Log\LoggerInterface build(array $config)
 * @method static \Illuminate\Log\Logger withContext(array $context = [])
 * @method static \Illuminate\Log\Logger withoutContext()
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void emergency(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void log($level, string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void write(string $level, string $message, array $context = [])
 * @method static void listen(\Closure $callback)
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
        return 'log';
    }
}
