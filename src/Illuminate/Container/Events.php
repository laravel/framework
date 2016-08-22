<?php

namespace Illuminate\Container;

use Closure;

class Events
{
	public $listeners = [];
	public $globalListeners = [];

	public function listen($subject, Closure $callback = null)
	{
        if ($callback === null && $subject instanceof Closure) {
			$this->globalListeners[] = $subject;
        } else {
			$subject = (is_object($subject)) ? get_class($subject) : (string) $subject;

			$this->listeners[$subject][] = $callback;
        }
	}

	public function fire($subject, $parameters = [])
	{
		$listeners = $this->getListenersFor($subject);

		self::callListeners($listeners, $parameters);
	}

	public function fireGlobal($parameters = [])
	{
		self::callListeners($this->globalListeners, $parameters);
	}

	public function getListenersFor($subject)
	{
		$callbacks = [];

		foreach ($this->listeners as $key => $value) {
			if ($key === $subject || $subject instanceof $key) {
				$callbacks = array_merge($callbacks, $value);
			}
		}

		return $callbacks;
	}

	public static function callListeners($listeners, $parameters = [])
	{
		foreach ($listeners as $listener) {
			call_user_func_array($listener, $parameters);
		}
	}
}
