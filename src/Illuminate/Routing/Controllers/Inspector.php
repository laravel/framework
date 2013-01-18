<?php namespace Illuminate\Routing\Controllers;

use ReflectionClass;
use ReflectionMethod;

class Inspector {

	/**
	 * An array of HTTP verbs.
	 *
	 * @var array
	 */
	protected $verbs = array('get', 'post', 'put', 'delete', 'head', 'options');

	/**
	 * Get the routable methods for a controller.
	 *
	 * @param  string  $controller
	 * @return array
	 */
	public function getRoutable($controller)
	{
		$routable = array();

		$reflection = new ReflectionClass($controller);

		// To get the routable methods, we will simply spin through all methods on the
		// controller instance checking to see if it belongs to the given class and
		// is a publicly routable method. If so, we will add it to this listings.
		foreach ($reflection->getMethods() as $method)
		{
			if ($this->isRoutable($method, $controller))
			{
				$routable[$method->name] = $this->getMethodData($method);
			}
		}

		return $routable;
	}

	/**
	 * Determine if the given controller method is routable.
	 *
	 * @param  ReflectionMethod  $method
	 * @param  string  $controller
	 * @return bool
	 */
	public function isRoutable(ReflectionMethod $method, $controller)
	{
		if ($method->class != $controller) return false;

		return $method->isPublic() and starts_with($method->name, $this->verbs);
	}

	/**
	 * Get the method data for a given method.
	 *
	 * @param  ReflectionMethod  $method
	 * @return array
	 */
	public function getMethodData(ReflectionMethod $method)
	{
		$name = $method->name;

		return array('verb' => $this->getVerb($name), 'uri' => $this->getUri($name));
	}

	/**
	 * Extract the verb from a controller action.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function getVerb($name)
	{
		return head(explode('_', snake_case($name)));
	}

	/**
	 * Determine the URI from the given method name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function getUri($name)
	{
		return implode('-', array_slice(explode('_', snake_case($name)), 1));
	}

}