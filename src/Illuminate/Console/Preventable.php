<?php

namespace Illuminate\Console;

trait Preventable
{
    protected static $preventFromRunning = false;

    /**
     * Whether to prevent command from running.
     *
     * @param  bool  $prevent
     * @return bool
     */
    public static function preventFromRunning($prevent = true)
    {
        static::$preventFromRunning = $prevent;
    }

    /**
     * Determine if the command is prevented from
     * running and display a warning if so.
     *
     * @return bool
     */
    protected function preventedFromRunning()
    {
        if (! static::$preventFromRunning) {
            return false;
        }

        $this->components->warn('Command has been prevented from running in this environment.');

        return true;
    }
}
