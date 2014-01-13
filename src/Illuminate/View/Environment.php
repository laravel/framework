<?php namespace Illuminate\View;

use Illuminate\Events\Dispatcher;
use Illuminate\View\Engines\EngineResolver;

/**
 * DEPRECATED: Please use Illuminate\View\Factory instead!
 */
class Environment extends Factory {

	/**
	 * Create a new view factory instance.
	 *
	 * @param  \Illuminate\View\Engines\EngineResolver  $engines
	 * @param  \Illuminate\View\ViewFinderInterface  $finder
	 * @param  \Illuminate\Events\Dispatcher  $events
	 * @return void
	 */
	public function __construct(EngineResolver $engines, ViewFinderInterface $finder, Dispatcher $events)
	{
		parent::__construct($engines, $finder, $events);
	}

}