<?php namespace Illuminate\Events;

use Exception;
use ReflectionClass;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Dispatcher implements DispatcherContract {

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * The registered event listeners.
	 *
	 * @var array
	 */
	protected $listeners = array();

	/**
	 * The wildcard listeners.
	 *
	 * @var array
	 */
	protected $wildcards = array();

	/**
	 * The sorted event listeners.
	 *
	 * @var array
	 */
	protected $sorted = array();

	/**
	 * The event firing stack.
	 *
	 * @var array
	 */
	protected $firing = array();

	/**
	 * The queue resolver instance.
	 *
	 * @var callable
	 */
	protected $queueResolver;

	/**
	 * Create a new event dispatcher instance.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @return void
	 */
	public function __construct(ContainerContract $container = null)
	{
		$this->container = $container ?: new Container;
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string|array  $events
	 * @param  mixed   $listener
	 * @param  int     $priority
	 * @return void
	 */
	public function listen($events, $listener, $priority = 0)
	{
		foreach ((array) $events as $event)
		{
			if (str_contains($event, '*'))
			{
				$this->setupWildcardListen($event, $listener);
			}
			else
			{
				$this->listeners[$event][$priority][] = $this->makeListener($listener);

				unset($this->sorted[$event]);
			}
		}
	}

	/**
	 * Setup a wildcard listener callback.
	 *
	 * @param  string  $event
	 * @param  mixed   $listener
	 * @return void
	 */
	protected function setupWildcardListen($event, $listener)
	{
		$this->wildcards[$event][] = $this->makeListener($listener);
	}

	/**
	 * Determine if a given event has listeners.
	 *
	 * @param  string  $eventName
	 * @return bool
	 */
	public function hasListeners($eventName)
	{
		return isset($this->listeners[$eventName]);
	}

	/**
	 * Register an event and payload to be fired later.
	 *
	 * @param  string  $event
	 * @param  array   $payload
	 * @return void
	 */
	public function push($event, $payload = array())
	{
		$this->listen($event.'_pushed', function() use ($event, $payload)
		{
			$this->fire($event, $payload);
		});
	}

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  string  $subscriber
	 * @return void
	 */
	public function subscribe($subscriber)
	{
		$subscriber = $this->resolveSubscriber($subscriber);

		$subscriber->subscribe($this);
	}

	/**
	 * Resolve the subscriber instance.
	 *
	 * @param  mixed  $subscriber
	 * @return mixed
	 */
	protected function resolveSubscriber($subscriber)
	{
		if (is_string($subscriber))
		{
			return $this->container->make($subscriber);
		}

		return $subscriber;
	}

	/**
	 * Fire an event until the first non-null response is returned.
	 *
	 * @param  string  $event
	 * @param  array   $payload
	 * @return mixed
	 */
	public function until($event, $payload = array())
	{
		return $this->fire($event, $payload, true);
	}

	/**
	 * Flush a set of pushed events.
	 *
	 * @param  string  $event
	 * @return void
	 */
	public function flush($event)
	{
		$this->fire($event.'_pushed');
	}

	/**
	 * Get the event that is currently firing.
	 *
	 * @return string
	 */
	public function firing()
	{
		return last($this->firing);
	}

	/**
	 * Fire an event and call the listeners.
	 *
	 * @param  string|object  $event
	 * @param  mixed   $payload
	 * @param  bool    $halt
	 * @return array|null
	 */
	public function fire($event, $payload = array(), $halt = false)
	{
		// When the given "event" is actually an object we will assume it is an event
		// object and use the class as the event name and this event itself as the
		// payload to the handler, which makes object based events quite simple.
		if (is_object($event))
		{
			list($payload, $event) = [[$event], get_class($event)];
		}

		$responses = array();

		// If an array is not given to us as the payload, we will turn it into one so
		// we can easily use call_user_func_array on the listeners, passing in the
		// payload to each of them so that they receive each of these arguments.
		if ( ! is_array($payload)) $payload = array($payload);

		$this->firing[] = $event;

		foreach ($this->getListeners($event) as $listener)
		{
			$response = call_user_func_array($listener, $payload);

			// If a response is returned from the listener and event halting is enabled
			// we will just return this response, and not call the rest of the event
			// listeners. Otherwise we will add the response on the response list.
			if ( ! is_null($response) && $halt)
			{
				array_pop($this->firing);

				return $response;
			}

			// If a boolean false is returned from a listener, we will stop propagating
			// the event to any further listeners down in the chain, else we keep on
			// looping through the listeners and firing every one in our sequence.
			if ($response === false) break;

			$responses[] = $response;
		}

		array_pop($this->firing);

		return $halt ? null : $responses;
	}

	/**
	 * Get all of the listeners for a given event name.
	 *
	 * @param  string  $eventName
	 * @return array
	 */
	public function getListeners($eventName)
	{
		$wildcards = $this->getWildcardListeners($eventName);

		if ( ! isset($this->sorted[$eventName]))
		{
			$this->sortListeners($eventName);
		}

		return array_merge($this->sorted[$eventName], $wildcards);
	}

	/**
	 * Get the wildcard listeners for the event.
	 *
	 * @param  string  $eventName
	 * @return array
	 */
	protected function getWildcardListeners($eventName)
	{
		$wildcards = array();

		foreach ($this->wildcards as $key => $listeners)
		{
			if (str_is($key, $eventName)) $wildcards = array_merge($wildcards, $listeners);
		}

		return $wildcards;
	}

	/**
	 * Sort the listeners for a given event by priority.
	 *
	 * @param  string  $eventName
	 * @return array
	 */
	protected function sortListeners($eventName)
	{
		$this->sorted[$eventName] = array();

		// If listeners exist for the given event, we will sort them by the priority
		// so that we can call them in the correct order. We will cache off these
		// sorted event listeners so we do not have to re-sort on every events.
		if (isset($this->listeners[$eventName]))
		{
			krsort($this->listeners[$eventName]);

			$this->sorted[$eventName] = call_user_func_array(
				'array_merge', $this->listeners[$eventName]
			);
		}
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  mixed   $listener
	 * @return mixed
	 */
	public function makeListener($listener)
	{
		return is_string($listener) ? $this->createClassListener($listener) : $listener;
	}

	/**
	 * Create a class based listener using the IoC container.
	 *
	 * @param  mixed    $listener
	 * @return \Closure
	 */
	public function createClassListener($listener)
	{
		$container = $this->container;

		return function() use ($listener, $container)
		{
			return call_user_func_array(
				$this->createClassCallable($listener, $container), func_get_args()
			);
		};
	}

	/**
	 * Create the class based event callable.
	 *
	 * @param  string  $listener
	 * @param  \Illuminate\Container\Container  $container
	 * @return callable
	 */
	protected function createClassCallable($listener, $container)
	{
		list($class, $method) = $this->parseClassCallable($listener);

		if ($this->handlerShouldBeQueued($class))
		{
			return $this->createQueuedHandlerCallable($class, $method);
		}
		else
		{
			return array($container->make($class), $method);
		}
	}

	/**
	 * Parse the class listener into class and method.
	 *
	 * @param  string  $listener
	 * @return array
	 */
	protected function parseClassCallable($listener)
	{
		$segments = explode('@', $listener);

		return [$segments[0], count($segments) == 2 ? $segments[1] : 'handle'];
	}

	/**
	 * Determine if the event handler class should be queued.
	 *
	 * @param  string  $class
	 * @return bool
	 */
	protected function handlerShouldBeQueued($class)
	{
		try
		{
			return (new ReflectionClass($class))->implementsInterface(
				'Illuminate\Contracts\Queue\ShouldBeQueued'
			);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Create a callable for putting an event handler on the queue.
	 *
	 * @param  string  $class
	 * @param  string  $method
	 * @return \Closure
	 */
	protected function createQueuedHandlerCallable($class, $method)
	{
		return function() use ($class, $method)
		{
			if (method_exists($class, 'queue'))
			{
				$this->callQueueMethodOnHandler($class, $method, func_get_args());
			}
			else
			{
				$this->resolveQueue()->push('Illuminate\Events\CallQueuedHandler@call', [
					'class' => $class, 'method' => $method, 'data' => serialize(func_get_args()),
				]);
			}
		};
	}

	/**
	 * Call the queue method on the handler class.
	 *
	 * @param  string  $class
	 * @param  string  $method
	 * @param  array  $arguments
	 * @return void
	 */
	protected function callQueueMethodOnHandler($class, $method, $arguments)
	{
		$handler = (new ReflectionClass($class))->newInstanceWithoutConstructor();

		$handler->queue($this->resolveQueue(), 'Illuminate\Events\CallQueuedHandler@call', [
			'class' => $class, 'method' => $method, 'data' => serialize($arguments),
		]);
	}

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param  string  $event
	 * @return void
	 */
	public function forget($event)
	{
		unset($this->listeners[$event], $this->sorted[$event]);
	}

	/**
	 * Forget all of the pushed listeners.
	 *
	 * @return void
	 */
	public function forgetPushed()
	{
		foreach ($this->listeners as $key => $value)
		{
			if (ends_with($key, '_pushed')) $this->forget($key);
		}
	}

	/**
	 * Get the queue implementation from the resolver.
	 *
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	protected function resolveQueue()
	{
		return call_user_func($this->queueResolver);
	}

	/**
	 * Set the queue resolver implementation.
	 *
	 * @param  callable  $resolver
	 * @return $this
	 */
	public function setQueueResolver(callable $resolver)
	{
		$this->queueResolver = $resolver;

		return $this;
	}

}
