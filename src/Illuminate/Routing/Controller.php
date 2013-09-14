<?php namespace Illuminate\Routing;

use Closure;

abstract class Controller {

	/**
	 * The "before" filters registered on the controller.
	 *
	 * @var array
	 */
	protected $beforeFilters = array();

	/**
	 * The "after" filters registered on the controller.
	 *
	 * @var array
	 */
	protected $afterFilters = array();

	/**
	 * The route filterer implementation.
	 *
	 * @var \Illuminate\Routing\RouteFiltererInterface
	 */
	protected static $filterer;

	/**
	 * Register a "before" filter on the controler.
	 *
	 * @param  \Closure|string  $name
	 * @param  array  $options
	 * @return void
	 */
	public function beforeFilter($filter, array $options = array())
	{
		$this->beforeFilters[] = $this->parseFilter($filter, $options);
	}

	/**
	 * Register an "after" filter on the controler.
	 *
	 * @param  \Closure|string  $name
	 * @param  array  $options
	 * @return void
	 */
	public function afterFilter($filter, array $options = array())
	{
		$this->afterFilters[] = $this->parseFilter($filter, $options);
	}

	/**
	 * Parse the given filter and options.
	 *
	 * @param  \Closure|string  $name
	 * @param  array  $options
	 * @return array
	 */
	protected function parseFilter($filter, array $options)
	{
		$parameters = array();

		if ($filter instanceof Closure)
		{
			$filter = $this->registerClosureFilter($filter);
		}
		else
		{
			list($filter, $parameters) = Route::parseFilter($filter);
		}

		return compact('filter', 'parameters', 'options');
	}

	/**
	 * Register an anonymous controller filter Closure.
	 *
	 * @param  \Closure  $filter
	 * @return string
	 */
	protected function registerClosureFilter(Closure $filter)
	{
		$this->getFilterer()->filter($name = spl_object_hash($filter), $filter);

		return $name;
	}

	/**
	 * Get the registered "before" filters.
	 *
	 * @return array
	 */
	public function getBeforeFilters()
	{
		return $this->beforeFilters;
	}

	/**
	 * Get the registered "after" filters.
	 *
	 * @return array
	 */
	public function getAfterFilters()
	{
		return $this->afterFilters;
	}

	/**
	 * Get the route filterer implementation.
	 *
	 * @return \Illuminate\Routing\RouteFiltererInterface
	 */
	public static function getFilterer()
	{
		return static::$filterer;
	}

	/**
	 * Set the route filterer implementation.
	 *
	 * @param  \Illuminate\Routing\RouteFiltererInterface  $filterer
	 * @return void
	 */
	public static function setFilterer(RouteFiltererInterface $filterer)
	{
		static::$filterer = $filterer;
	}

}