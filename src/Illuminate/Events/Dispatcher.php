<?php namespace Illuminate\Events;

use Illuminate\Container;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyDispatcher;

class Dispatcher extends SymfonyDispatcher {

	/**
	 * The IoC container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * Create a new event dispatcher instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @return void
	 */
	public function __construct(Container $container = null)
	{
		$this->container = $container;
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string  $event
	 * @param  mixed   $listener
	 * @param  int     $priority
	 * @return void
	 */
	public function listen($event, $listener, $priority = 0)
	{
		return $this->addListener($event, $listener, $priority);
	}

	/**
	 * Fire an event and call the listeners.
	 *
	 * @param  string  $eventName
	 * @param  mixed   $payload
	 * @return Symfony\Component\EventDispatcher\Event
	 */
	public function fire($eventName, $payload = array())
	{
		if ( ! $payload instanceof SymfonyEvent)
		{
			$payload = new Event($payload);
		}

		return parent::dispatch($eventName, $payload);
	}

	/**
	 * Register an event subscriber class.
	 *
	 * @param  Symfony\Component\EventDispatcher\EventSubscriberInterface  $subscriber
	 * @return void
	 */
	public function subscribe(EventSubscriberInterface $subscriber)
	{
		return parent::addSubscriber($subscriber);
	}

	/**
	 * Remove an event subscriber.
	 *
	 * @param  Symfony\Component\EventDispatcher\EventSubscriberInterface  $subscriber
	 * @return void
	 */
	public function unsubscribe(EventSubscriberInterface $subscriber)
	{
		return parent::removeSubscriber($subscriber);
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string  $event
	 * @param  mixed   $listener
	 * @param  int     $priority
	 * @return void
	 */
	public function addListener($eventName, $listener, $priority = 0)
	{
		if (is_string($listener))
		{
			$listener = $this->createClassListener($listener);
		}

		return parent::addListener($eventName, $listener, $priority);
	}

	/**
	 * Create a class based listener using the IoC container.
	 *
	 * @param  mixed    $listener
	 * @return Closure
	 */
	public function createClassListener($listener)
	{
		$container = $this->container;

		return function(SymfonyEvent $event) use ($listener, $container)
		{
			// If the listener has a colon, we will assume it is being used to delimit
			// the class name from the handle method name. This allows for handlers
			// to run multiple handler methods in a single class for convenience.
			$segments = explode('@', $listener);

			$method = count($segments) == 2 ? $segments[1] : 'handle';

			$container->make($segments[0])->$method($event);
		};
	}

}