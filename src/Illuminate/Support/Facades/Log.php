<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Psr\Log\LoggerInterface build(array $config)
 * @method static \Psr\Log\LoggerInterface stack(array $channels, string|null $channel = null)
 * @method static \Psr\Log\LoggerInterface channel(string|null $channel = null)
 * @method static \Psr\Log\LoggerInterface driver(string|null $driver = null)
 * @method static \Illuminate\Log\LogManager shareContext(array $context)
 * @method static array sharedContext()
 * @method static \Illuminate\Log\LogManager flushSharedContext()
 * @method static string|null getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static \Illuminate\Log\LogManager extend(string $driver, \Closure $callback)
 * @method static void forgetChannel(string|null $driver = null)
 * @method static array getChannels()
 * @method static void emergency(string $message, array $context = [])
 * @method static void alert(mixed $message, array $context = [])
 * @method static void critical(mixed $message, array $context = [])
 * @method static void error(mixed $message, array $context = [])
 * @method static void warning(mixed $message, array $context = [])
 * @method static void notice(mixed $message, array $context = [])
 * @method static void info(mixed $message, array $context = [])
 * @method static void debug(mixed $message, array $context = [])
 * @method static void log(mixed $level, mixed $message, array $context = [])
 * @method static void write(mixed $level, mixed $message, array $context = [])
 * @method static \Illuminate\Log\Logger withContext(array $context = [])
 * @method static \Illuminate\Log\Logger withoutContext()
 * @method static void listen(\Closure $callback)
 * @method static \Psr\Log\LoggerInterface getLogger()
 * @method static \Illuminate\Contracts\Events\Dispatcher getEventDispatcher()
 * @method static void setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $dispatcher)
 *
 * @see \Illuminate\Log\LogManager
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
