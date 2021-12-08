<?php

namespace Illuminate\Support\Hooks;

use Closure;
use Illuminate\Contracts\Support\Hook as HookContract;

class Hook implements HookContract
{
    /**
     * A cleanup function that should be run after the hooks are run.
     *
     * @var \Closure|null
     */
    protected ?Closure $cleanup = null;

    /**
     * Instantiate a new high-priority hook.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return \Illuminate\Support\Hooks\Hook
     */
    public static function highPriority(string $name, Closure $callback): Hook
    {
        return new static($name, $callback, HookContract::PRIORITY_HIGH);
    }

    /**
     * Instantiate a new hook.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return \Illuminate\Support\Hooks\Hook
     */
    public static function make(string $name, Closure $callback): Hook
    {
        return new static($name, $callback, HookContract::PRIORITY_NORMAL);
    }

    /**
     * Instantiate a new low-priority hook.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return \Illuminate\Support\Hooks\Hook
     */
    public static function lowPriority(string $name, Closure $callback): Hook
    {
        return new static($name, $callback, HookContract::PRIORITY_LOW);
    }

    /**
     * Constructor.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @param  int  $priority
     * @return void
     */
    public function __construct(
        public string $name,
        public Closure $callback,
        public int $priority = HookContract::PRIORITY_NORMAL
    ) {
    }

    /**
     * @inheritdoc
     */
    public function run($instance, array $arguments = [])
    {
        $this->cleanup = null;

        $result = $this->runCallback($this->callback, $instance, $arguments);

        if ($result instanceof Closure) {
            $this->cleanup = $result;
        }
    }

    /**
     * @inheritdoc
     */
    public function cleanup($instance, array $arguments = [])
    {
        if (is_null($this->cleanup)) {
            return;
        }

        $this->runCallback($this->cleanup, $instance, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Run a callback that may or may not be static.
     *
     * @param  Closure  $callback
     * @param  object|string  $instance
     * @param  array  $arguments
     * @return mixed
     */
    protected function runCallback($callback, $instance, $arguments = [])
    {
        return is_object($instance)
            ? $callback->call($instance, $arguments)
            : $callback(...$arguments);
    }
}
