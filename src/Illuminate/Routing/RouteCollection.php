<?php namespace Illuminate\Routing;

use Symfony\Component\Routing\RouteCollection as SymfonyCollection;

class RouteCollection extends SymfonyCollection {

	/**
	 * The map of action to names for controllers methods.
	 *
	 * @var array
	 */
	protected $actionMap = array();

	/**
	 * The map of base URLs mapped by action.
	 *
	 * @var array
	 */
	protected $actionBases = array();

	/**
	 * Map the action to a given route name.
	 *
	 * @param  string  $action
	 * @param  string  $name
	 * @return void
	 */
	public function mapAction($action, $name)
	{
		$this->actionMap[$action] = $name;
	}

	/**
	 * Map a controller name to a base URL.
	 *
	 * @param  string  $controller
	 * @param  string  $uri
	 * @param  string  $domain
	 * @return void
	 */
	public function mapBase($controller, $uri, $domain = null)
	{
		$this->actionBases[$controller] = compact('uri', 'domain');
	}

	/**
	 * Get the route name for a controller action.
	 *
	 * @param  string  $action
	 * @return string
	 */
	public function getMapped($action)
	{
		if (isset($this->actionMap[$action]))
		{
			return $this->actionMap[$action];
		}
	}

	/**
	 * Get the base URI for a given controller.
	 *
	 * @param  string  $action
	 * @return string
	 */
	public function getMappedBase($action)
	{
		list($controller, $method) = explode('@', $action);

		if (isset($this->actionBases[$controller]))
		{
			$base = $this->actionBases[$controller];

			return array('domain' => $base['domain'], 'uri' => $base['uri'].'/'.$this->getActionMethod($method));
		}
	}

	/**
	 * Get the method in snake-case form.
	 *
	 * @param  string  $method
	 * @return string
	 */
	protected function getActionMethod($method)
	{
		$method = snake_case($method, '-');

		return substr($method, strpos($method, '-') + 1);
	}

}