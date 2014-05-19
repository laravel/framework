<?php namespace Illuminate\Database\Eloquent;

use Illuminate\Events\Dispatcher;

trait ObservableTrait {

	/**
	 * User exposed observable events
	 *
	 * @var array
	 */
	protected $observables = [];

	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Events\Dispatcher
	 */
	protected static $dispatcher;

	/**
	 * Register an observer with the Model.
	 *
	 * @param  object  $class
	 * @return void
	 */
	public static function observe($class)
	{
		$instance = new static;

		$className = get_class($class);

		// When registering a model observer, we will spin through the possible events
		// and determine if this observer has that method. If it does, we will hook
		// it into the model's event system, making it convenient to watch these.
		foreach ($instance->getObservableEvents() as $event)
		{
			if (method_exists($class, $event))
			{
				static::registerObservableEvent($event, $className.'@'.$event);
			}
		}
	}

	/**
	 * Get the observer key.
	 *
	 * @param  string   $event
	 * @return string
	 */
	protected static function getObservableKey($event)
	{
		return $event;
	}

	/**
	 * Register a model event with the dispatcher.
	 *
	 * @param  string  $event
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	protected static function registerObservableEvent($event, $callback)
	{
		if (isset(static::$dispatcher))
		{
			$name = get_called_class();
			$event = static::getObservableKey($event);

			static::$dispatcher->listen("{$event}: {$name}", $callback);
		}
	}

	/**
	 * Fire the given event for the model.
	 *
	 * @param  string  $event
	 * @param  bool    $halt
	 * @return mixed
	 */
	protected function fireObservableEvent($event, $halt = true)
	{
		if ( ! isset(static::$dispatcher)) return true;

		// We will append the names of the class to the event to distinguish it from
		// other model events that are fired, allowing us to listen on each model
		// event set individually instead of catching event for all the models.
		$event = "{$event}: ".get_class($this);

		$method = $halt ? 'until' : 'fire';

		return static::$dispatcher->$method(static::getObservableKey($event), $this);
	}

	/**
	 * Remove all of the event listeners for the model.
	 *
	 * @return void
	 */
	public static function flushEventListeners()
	{
		if ( ! isset(static::$dispatcher)) return;

		$instance = new static;

		foreach ($instance->getObservableEvents() as $event)
		{
			$event = static::getObservableKey($event);

			static::$dispatcher->forget("{$event}: ".get_called_class());
		}
	}

	/**
	 * Get the event dispatcher instance.
	 *
	 * @return \Illuminate\Events\Dispatcher
	 */
	public static function getEventDispatcher()
	{
		return static::$dispatcher;
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param  \Illuminate\Events\Dispatcher  $dispatcher
	 * @return void
	 */
	public static function setEventDispatcher(Dispatcher $dispatcher)
	{
		static::$dispatcher = $dispatcher;
	}

	/**
	 * Unset the event dispatcher for models.
	 *
	 * @return void
	 */
	public static function unsetEventDispatcher()
	{
		static::$dispatcher = null;
	}
}
