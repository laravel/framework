<?php

namespace Illuminate\Support;

use Symfony\Component\VarDumper\VarDumper as SymfonyVarDumper;

class VarDumper
{
    /**
     * Times to dump.
     *
     * @var int
     */
    private static int $times = 0;

    /**
     * Determines it's on testing or not.
     *
     * @var bool
     */
    private static bool $testing = false;

    /**
     * Number of something has dumped.
     *
     * @var int
     */
    private static int $dumpedCount = 0;

    /**
     * Gets those that has dumped.
     *
     * @var array
     */
    private static array $dumpedItems = [];

    /**
     * Determines that died.
     *
     * @var bool
     */
    private static bool $died = false;

    /**
     * Sets testing environment.
     *
     * @return void
     */
    public static function fake()
    {
        self::$testing = true;
    }

    /**
     * Dumps and dies after the given time.
     *
     * @var int $times
     * @var array $vars
     * @return void|Symfony\Component\VarDumper\VarDumper
     */
    public static function ddt(int $times, ...$vars)
    {
        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) && !headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        if (self::$died) {
            return;
        }

        if (! $times) {
            return self::exit(1);
        }

        if (self::$times === 0) {
            self::$times = $times;
        }

        foreach ($vars as $v) {
            self::$dumpedItems[] = $v;

            if (! self::$testing) {
                SymfonyVarDumper::dump($v);
                continue;
            }

            self::$dumpedCount ++;
        }

        self::$times --;

        if (! self::moreTimes()) {
            self::$times = 0;

            return self::exit(1);
        }
    }

    /**
     * Gets number of something has dumped.
     *
     * @return int
     */
    public static function getDumpedCount()
    {
        return self::$dumpedCount;
    }

    /**
     * Gets dumped things.
     *
     * @return array
     */
    public static function getDumpedItems()
    {
        return self::$dumpedItems;
    }

    /**
     * Gets that is died.
     *
     * @return bool
     */
    public static function died()
    {
        return self::$died;
    }

    /**
     * Resets properties.
     *
     * @return void
     */
    public static function reset()
    {
        $defaultPropertiesValue = [
            'times' => 0,
            'testing' => false,
            'dumpedCount' => 0,
            'dumpedItems' => [],
            'died' => false,
        ];

        foreach ($defaultPropertiesValue as $property => $default) {
            self::${$property} = $default;
        }
    }

    /**
     * Exists from dumping.
     *
     * @var string|int
     * @return void
     */
    private static function exit($status)
    {
        if (! self::$testing) {
            exit($status);
        }

        self::$died = true;

        return;
    }

    /**
     * Determines is there more time to dump.
     *
     * @return bool
     */
    private static function moreTimes()
    {
        return self::$times !== 0;
    }
}
