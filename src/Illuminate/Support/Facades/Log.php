<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\LogFake;

/**
 * @method static void emergency(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void log($level, string $message, array $context = [])
 * @method static mixed channel(string $channel = null)
 * @method static \Psr\Log\LoggerInterface stack(array $channels, string $channel = null)
 * @method static void assertLogged($level, $callback = null)
 * @method static void assertLoggedTimes($level, $times = 1)
 * @method static void assertNotLogged($level, $callback = null)
 * @method static void assertNothingLogged()
 * @method static \Illuminate\Support\Collection logged($level, $callback = null)
 * @method static bool hasLogged($level)
 * @method static bool hasNotLogged($level)
 *
 * @see \Illuminate\Log\Logger
 * @see \Illuminate\Support\Testing\Fakes\LogFake
 */
class Log extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return void
     */
    public static function fake()
    {
        static::swap(new LogFake);
    }

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
