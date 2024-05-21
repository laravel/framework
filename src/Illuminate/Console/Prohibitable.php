<?php

namespace Illuminate\Console;

trait Prohibitable
{
    /**
     * Indicates if the command should be prohibited from running.
     *
     * @var bool
     */
    protected static $prohibitedFromRunning = false;

    /**
     * Indicate whether the command should be prohibited from running.
     *
     * @param  bool  $prevent
     * @return bool
     */
    public static function prohibit($prevent = true)
    {
        static::$prohibitedFromRunning = $prevent;
    }

    /**
     * Determine if the command is prohibited from running and display a warning if so.
     *
     * @param  bool  $quiet
     * @return bool
     */
    protected function isProhibited(bool $quiet = false)
    {
        if (! static::$prohibitedFromRunning) {
            return false;
        }

        if (! $quiet) {
            $this->components->warn('Command is prohibited from running in this environment.');
        }

        return true;
    }
}
