<?php namespace Illuminate\Contracts\Routing;

interface FilterableController {

	/**
	 * Register a "before" filter on the controller.
	 *
	 * @param  \Closure|string  $filter
	 * @param  array  $options
	 * @return void
	 */
	public function beforeFilter($filter, array $options = array());

	/**
	 * Register an "after" filter on the controller.
	 *
	 * @param  \Closure|string  $filter
	 * @param  array  $options
	 * @return void
	 */
	public function afterFilter($filter, array $options = array());

	/**
	 * Remove the given before filter.
	 *
	 * @param  string  $filter
	 * @return void
	 */
	public function forgetBeforeFilter($filter);

	/**
	 * Remove the given after filter.
	 *
	 * @param  string  $filter
	 * @return void
	 */
	public function forgetAfterFilter($filter);

	/**
	 * Get the registered "before" filters.
	 *
	 * @return array
	 */
	public function getBeforeFilters();

	/**
	 * Get the registered "after" filters.
	 *
	 * @return array
	 */
	public function getAfterFilters();

}
