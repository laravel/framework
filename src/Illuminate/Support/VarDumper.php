<?php

namespace Illuminate\Support;

use Symfony\Component\VarDumper\VarDumper;

class VarDumper
{
    /**
     * Times to dump.
     *
     * @var int
     */
    private static int $times = 0;

    /**
     * Dumps and dies after the given time.
     */
    public static function ddt(int $times, ...$vars)
    {
        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) && !headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        if (self::$times === 0) {
            self::$times = $times;
        }

        foreach ($vars as $v) {
            VarDumper::dump($v);
        }

        self::$times --;

        if (!self::moreTimes()) {
            self::$times = 0;

            exit(1);
        }
    }

    /**
     * Determines is there more time to dump.
     */
    private function moreTimes(): bool
    {
        return self::$times !== 0;
    }
}
