<?php

namespace Illuminate\Console;

use Closure;

trait Prohibitable
{
    /**
     * Indicates if the command should be prohibited from running.
     *
     * @var bool|(\Closure(\Symfony\Component\Console\Input\InputInterface): bool)
     */
    protected static $prohibitedFromRunning = false;

    /**
     * Indicate whether the command should be prohibited from running.
     *
     * Pass a closure to prohibit the command only for specific input. The
     * closure receives the command's input and should return true to
     * prohibit the command from running for that invocation.
     *
     * @param  bool|(\Closure(\Symfony\Component\Console\Input\InputInterface): bool)  $prohibit
     * @return void
     */
    public static function prohibit($prohibit = true)
    {
        static::$prohibitedFromRunning = $prohibit;
    }

    /**
     * Determine if the command is prohibited from running and display a warning if so.
     *
     * @param  bool  $quiet
     * @return bool
     */
    protected function isProhibited(bool $quiet = false)
    {
        $prohibited = static::$prohibitedFromRunning instanceof Closure
            ? (bool) (static::$prohibitedFromRunning)($this->input)
            : static::$prohibitedFromRunning;

        if (! $prohibited) {
            return false;
        }

        if (! $quiet) {
            $this->components->warn('This command is prohibited from running in this environment.');
        }

        return true;
    }
}
