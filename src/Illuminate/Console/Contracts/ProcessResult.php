<?php

namespace Illuminate\Console\Contracts;

/**
 * @internal
 */
interface ProcessResult
{
    /**
     * Get the process's output.
     *
     * @return string
     */
    public function output();

    /**
     * Determine if the process has run successfully.
     *
     * @return bool
     */
    public function ok();

    /**
     * Determine if the process has failed.
     *
     * @return bool
     */
    public function failed();

    /**
     * Waits for the process to finish.
     *
     * @return $this
     */
    public function wait();

    /**
     * Throw an exception if the process fails.
     *
     * @param  (callable(\Illuminate\Console\Exceptions\ProcessFailedException): mixed)|null  $callback
     * @return $this
     *
     * @throws \Illuminate\Console\Exceptions\ProcessFailedException
     */
    public function throw($callback = null);

    /**
     * Throw an exception if the process fails and the given condition evaluates to true.
     *
     * @param  bool  $condition
     * @return $this
     *
     * @throws \Illuminate\Console\Exceptions\ProcessFailedException
     */
    public function throwIf($condition);

    /**
     * Throw an exception if the process fails and the given condition evaluates to false.
     *
     * @param  bool  $condition
     * @return $this
     *
     * @throws \Illuminate\Console\Exceptions\ProcessFailedException
     */
    public function throwUnless($condition);
}
