<?php namespace Illuminate\Routing\Controllers;

use Symfony\Component\HttpFoundation\Request;

class Filter {

	/**
	 * The name of the filter to be applied.
	 *
	 * @var string
	 */
	public $run;

	/**
	 * The HTTP methods the filter applies to.
	 *
	 * @var array
	 */
	public $on;

	/**
	 * The controller methods the filter applies to.
	 *
	 * @var array
	 */
	public $only;

	/**
	 * The controller methods the filter doesn't apply to.
	 *
	 * @var array
	 */
	public $except;

	/**
	 * Create a new annotation instance.
	 *
	 * @param  array  $values
	 * @return void
	 */
	public function __construct(array $values)
	{
		foreach ($this->prepareValues($values) as $key => $value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Prepare the values for setting.
	 *
	 * @param  array  $values
	 * @return void
	 */
	protected function prepareValues($values)
	{
		if (isset($values['on']))
		{
			$values['on'] = (array) $values['on'];

			// If the "get" method is present in an "on" constraint for the annotation we
			// will add the "head" method as well, since the "head" method is supposed
			// to function basically identically to the get methods on the back-end.
			if (in_array('get', $values['on']))
			{
				$values['on'][] = 'head';
			}
		}

		return $values;
	}

	/**
	 * Determine if the filter applies to a request and method.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return bool
	 */
	public function applicable(Request $request, $method)
	{
		foreach (array('Request', 'OnlyMethod', 'ExceptMethod') as $excluder)
		{
			// We'll simply check the excluder method and see if the annotation does
			// not apply based on that rule. If it does not, we will go ahead and
			// return false since we know an annotation is not even applicable.
			$excluder = "excludedBy{$excluder}";

			if ($this->$excluder($request, $method)) return false;
		}

		return true;
	}

	/**
	 * Determine if the filter applies based on the "on" rule.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return bool
	 */
	protected function excludedByRequest($request, $method)
	{
		$http = strtolower($request->getMethod());

		return isset($this->on) and ! in_array($http, (array) $this->on);
	}

	/**
	 * Determine if the filter applies based on the "only" rule.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return bool
	 */
	protected function excludedByOnlyMethod($request, $method)
	{
		return isset($this->only) and ! in_array($method, (array) $this->only);
	}

	/**
	 * Determine if the filter applies based on the "except" rule.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return bool
	 */
	protected function excludedByExceptMethod($request, $method)
	{
		return isset($this->except) and in_array($method, (array) $this->except);
	}

}