<?php

namespace Illuminate\Contracts\Support;

interface Hook
{
    /**
     * Indicates this hook should be run with higher priority.
     *
     * @var int
     */
    public const PRIORITY_HIGH = 100;

    /**
     * Indicates this hook should be run with normal priority.
     *
     * @var int
     */
    public const PRIORITY_NORMAL = 200;

    /**
     * Indicates this hook should be run with lower priority.
     *
     * @var int
     */
    public const PRIORITY_LOW = 300;

    /**
     * Run the hook.
     *
     * @param  object|string  $instance
     * @param  array  $arguments
     */
    public function run($instance, array $arguments = []);

    /**
     * Clean up after the hook.
     *
     * @param  object|string  $instance
     * @param  array  $arguments
     */
    public function cleanup($instance, array $arguments = []);
}
