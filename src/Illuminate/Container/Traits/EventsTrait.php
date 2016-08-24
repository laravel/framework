<?php

namespace Illuminate\Container\Traits;

use Closure;
use ReflectionClass;

trait EventsTrait
{
	private $afterResolving = [];
	private $globalAfterResolving = [];

    /**
     * Register a new resolving callback.
     *
     * @param  string    $abstract
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
     * @param  string    $abstract
     * @param  \Closure|null  $callback
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null)
    {
        $abstract = $this->normalize($abstract);

        if ($callback === null && $abstract instanceof Closure) {
			$this->globalAfterResolving[] = $abstract;
        } else {
			$abstract = (is_object($abstract)) ? get_class($abstract) : (string) $abstract;

			$this->afterResolving[$abstract][] = $callback;
        }
    }

    public function fireAfterResolving($abstract, $resolved)
    {
    	$callbacks = $this->globalAfterResolving;

    	if (is_object($resolved)) {
    		foreach ($this->afterResolving as $key => $value) {
    			if ($abstract === $key || $resolved instanceof $key) {
    				$callbacks = array_merge($callbacks, $value);
    			}
    		}
    	} elseif (is_string($abstract)) {
    		if (isset($this->afterResolving[$abstract])) {
    			$callbacks = array_merge($callbacks, $this->afterResolving[$abstract]);
    		}
    	}

    	self::fireCallbacks($callbacks, [$resolved, $this]);
    }

    public static function fireCallbacks(array $callbacks, array $parameters = [])
    {
    	foreach ($callbacks as $callback) {
    		call_user_func_array($callback, $parameters);
    	}
    }
}
