<?php

namespace Illuminate\Console\Contracts;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use Stringable;

/**
 * @extends Arrayable<int, string>
 * @extends IteratorAggregate<int, string>
 * @extends ArrayAccess<int, string>
 */
interface ProcessResult extends Arrayable, Stringable, ArrayAccess, IteratorAggregate
{
    /**
     * Get the process's output.
     *
     * @return string
     */
    public function output();

    /**
     * Get the process's error output.
     *
     * @return string
     */
    public function errorOutput();

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
     * Checks if the process is running.
     *
     * @return bool
     */
    public function running();

    /**
     * Waits for the process to finish.
     *
     * @return $this
     */
    public function wait();

    /**
     * Get the process's exit code.
     *
     * @return int
     */
    public function exitCode();

    /**
     * Get the underlying process.
     *
     * @return \Illuminate\Console\Process
     */
    public function process();

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

    /**
     * Get the process's output.
     *
     * @return string
     */
    public function toString();
}
