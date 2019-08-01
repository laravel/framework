<?php

namespace Illuminate\Support;

use Carbon\Carbon as BaseCarbon;

class Carbon extends BaseCarbon
{
    /**
     * Temporarily freeze time for the closure.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function freeze(callable $callback)
    {
        static::freezeAt(static::now(), $callback);
    }

    /**
     * Temporarily freeze time at the given time for the closure.
     *
     * @param  \Illuminate\Support\Carbon  $now
     * @param  callable  $callback
     * @return void
     */
    public static function freezeAt(self $now, callable $callback)
    {
        static::setTestNow($now);

        try {
            $callback($now);
        } finally {
            static::setTestNow();
        }
    }
}
