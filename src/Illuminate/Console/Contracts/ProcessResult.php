<?php

namespace Illuminate\Console\Contracts;

interface ProcessResult
{
    /**
     * Get the original command executed by the process.
     *
     * @return string
     */
    public function command();

    /**
     * Determine if the process was successful.
     *
     * @return bool
     */
    public function successful();

    /**
     * Get the exit code of the process.
     *
     * @return int
     */
    public function exitCode();

    /**
     * Get the standard output of the process.
     *
     * @return string
     */
    public function output();

    /**
     * Get the error output of the process.
     *
     * @return string
     */
    public function errorOutput();

    /**
     * Throw an exception if the process failed.
     *
     * @param  callable|null  $callback
     * @return $this
     */
    public function throw($callback = null);

    /**
     * Throw an exception if the process failed and the given condition is true.
     *
     * @param  bool  $condition
     * @param  callable|null  $callback
     * @return $this
     */
    public function throwIf($condition, $callback = null);
}
