<?php

namespace Illuminate\Support\Hooks;

use Closure;
use Illuminate\Contracts\Support\Hook;
use Illuminate\Support\Str;
use PHPUnit\Framework\MockObject\BadMethodCallException;
use RuntimeException;

class PendingHook
{
    /**
     * The resolved hook.
     *
     * @var Hook|null
     */
    protected ?Hook $hook = null;

    /**
     * Constructor.
     *
     * @param  \Closure  $callback
     * @param  bool  $isStatic
     * @return void
     */
    public function __construct(
        public Closure $callback,
        public bool $isStatic
    ) {
    }

    /**
     * Resolve the pending hook into a Hook instance.
     *
     * @param  object|string|null  $instance
     * @return \Illuminate\Support\Hooks\Hook
     */
    public function resolve($instance = null)
    {
        if (is_object($instance) || $this->isStatic) {
            return $this->getHook($instance);
        }

        throw new RuntimeException('Trying to resolve a non-static hook statically.');
    }

    /**
     * Resolve or return the already resolved Hook.
     *
     * @param  object|string|null  $instance
     * @return \Illuminate\Support\Hooks\Hook
     */
    protected function getHook($instance = null): Hook
    {
        if (is_null($this->hook)) {
            try {
                $this->hook = $this->isStatic
                    ? call_user_func($this->callback)
                    : $this->callback->call($instance);
            } catch (BadMethodCallException $exception) {
                $this->hook = new class implements Hook
                {
                    public function run($instance, array $arguments = [])
                    {
                        throw new RuntimeException('Unexpected hook call from mock object');
                    }

                    public function cleanup($instance, array $arguments = [])
                    {
                        throw new RuntimeException('Unexpected hook call from mock object');
                    }

                    public function getName()
                    {
                        return Str::random();
                    }

                    public function getPriority()
                    {
                        return PHP_INT_MAX;
                    }
                };
            }
        }

        return $this->hook;
    }
}
