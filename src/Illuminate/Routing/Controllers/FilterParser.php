<?php namespace Illuminate\Routing\Controllers;

use Symfony\Component\HttpFoundation\Request;

class FilterParser {

	/**
	 * Parse the given filters from the controller.
	 *
	 * @param  \Illuminate\Routing\Controllers\Controller  $controller
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @return array
	 */
	public function parse(Controller $controller, Request $request, $method, $filter)
	{
		return $this->getCodeFilters($controller, $request, $method, $filter);
	}

	/**
	 * Get the filters that were specified in code.
	 *
	 * @param  \Illuminate\Routing\Controllers\Controller  $controller
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @return array
	 */
	protected function getCodeFilters($controller, $request, $method, $filter)
	{
		$filters = $this->filterByClass($controller->getControllerFilters(), $filter);

		return $this->getNames($this->filter($filters, $request, $method));
	}

	/**
	 * Filter the annotation instances by class name.
	 *
	 * @param  array   $filters
	 * @param  string  $filter
	 * @return array
	 */
	protected function filterByClass($filters, $filter)
	{
		return array_filter($filters, function($annotation) use ($filter)
		{
			return $annotation instanceof $filter;
		});
	}

	/**
	 * Filter the annotation instances by request and method.
	 *
	 * @param  array  $filters
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return array
	 */
	protected function filter($filters, $request, $method)
	{
		$filtered = array_filter($filters, function($annotation) use ($request, $method)
		{
			return $annotation->applicable($request, $method);
		});

		return array_values($filtered);
	}

	/**
	 * Get the filter names from an array of filter objects.
	 *
	 * @param  array  $filters
	 * @return array
	 */
	protected function getNames(array $filters)
	{
		return array_map(function($filter) { return $filter->run; }, $filters);
	}

}