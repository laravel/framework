<?php

namespace Illuminate\Container\Traits;

use Closure;

trait EventsTrait
{
	private $afterResolving = [];
	private $globalAfterResolving = [];

    /**
     * Register a new resolving callback.
     *
     * @param  string         $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function resolving($abstract, Closure $callback = null)
    {
        return $this->afterResolving($abstract, $callback);
    }

   /**
     * Register a new after resolving callback.
     *
     * @param  string         $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null)
    {
        $abstract = self::normalize($abstract);

        if ($callback === null && $abstract instanceof Closure) {
			$this->globalAfterResolving[] = $abstract;
        } else {
			$this->afterResolving[$abstract][] = $callback;
        }
    }

    /**
     * Call the callbacks
     *
     * @param  mixed $concrete
     * @param  mixed $resolved
     * @param  mixed $abstract
     * @return void
     */
    private function afterResolvingCallback($concrete, $resolved, $abstract = null)
    {
        $callbacks = $this->globalAfterResolving;

        if (is_string($concrete) && isset($this->afterResolving[$concrete])) {
            $callbacks = array_merge($callbacks, $this->afterResolving[$concrete]);
        }
        if (is_string($abstract) && isset($this->afterResolving[$abstract])) {
            $callbacks = array_merge($callbacks, $this->afterResolving[$abstract]);
        }

        foreach ($callbacks as $callback) {
            $callback($resolved, $this);
        }
    }
}
