<?php namespace Illuminate\Routing;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class UrlGenerator {

	/**
	 * The route collection.
	 *
	 * @var \Illuminate\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * The request instance.
	 *
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * Create a new URL Generator instance.
	 *
	 * @param  \Illuminate\Routing\RouteCollection  $routes
	 * @param  \Symfony\Component\HttpFoundation\Request   $request
	 * @return void
	 */
	public function __construct(RouteCollection $routes, Request $request)
	{
		$this->routes = $routes;

		$this->setRequest($request);
	}

	/**
	 * Get the full URL for the current request.
	 *
	 * @return string
	 */
	public function full()
	{
		return $this->request->fullUrl();
	}

	/**
	 * Get the current URL for the request.
	 *
	 * @return string
	 */
	public function current()
	{
		return $this->to($this->request->getPathInfo());
	}

	/**
	 * Get the URL for the previous request.
	 *
	 * @return string
	 */
	public function previous()
	{
		return $this->to($this->request->headers->get('referer'));
	}

	/**
	 * Generate a absolute URL to the given path.
	 *
	 * @param  string  $path
	 * @param  mixed  $extra
	 * @param  bool  $secure
	 * @return string
	 */
	public function to($path, $extra = array(), $secure = null)
	{
		// First we will check if the URL is already a valid URL. If it is we will not
		// try to generate a new one but will simply return the URL as is, which is
		// convenient since developers do not always have to check if it's valid.
		if ($this->isValidUrl($path)) return $path;

		$scheme = $this->getScheme($secure);

		$tail = implode('/', (array) $extra);

		// Once we have the scheme we will compile the "tail" by collapsing the values
		// into a single string delimited by slashes. This just makes it convenient
		// for passing the array of parameters to this URL as a list of segments.
		$root = $this->getRootUrl($scheme);

		return $this->trimUrl($root, $path, $tail);
	}

	/**
	 * Generate a secure, absolute URL to the given path.
	 *
	 * @param  string  $path
	 * @param  array   $parameters
	 * @return string
	 */
	public function secure($path, $parameters = array())
	{
		return $this->to($path, $parameters, true);
	}

	/**
	 * Generate a URL to an application asset.
	 *
	 * @param  string  $path
	 * @param  bool    $secure
	 * @return string
	 */
	public function asset($path, $secure = null)
	{
		if ($this->isValidUrl($path)) return $path;

		// Once we get the root URL, we will check to see if it contains an index.php
		// file in the paths. If it does, we will remove it since it is not needed
		// for asset paths, but only for routes to endpoints in the application.
		$root = $this->getRootUrl($this->getScheme($secure));

		return $this->removeIndex($root).'/'.trim($path, '/');
	}

	/**
	 * Remove the index.php file from a path.
	 *
	 * @param  string  $root
	 * @return string
	 */
	protected function removeIndex($root)
	{
		$i = 'index.php';

		return str_contains($root, $i) ? str_replace('/'.$i, '', $root) : $root;
	}

	/**
	 * Generate a URL to a secure asset.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function secureAsset($path)
	{
		return $this->asset($path, true);
	}

	/**
	 * Get the scheme for a raw URL.
	 *
	 * @param  bool    $secure
	 * @return string
	 */
	protected function getScheme($secure)
	{
		if (is_null($secure))
		{
			return $this->request->getScheme().'://';
		}
		else
		{
			return $secure ? 'https://' : 'http://';
		}
	}

	/**
	 * Get the URL to a named route.
	 *
	 * @param  string  $name
	 * @param  mixed   $parameters
	 * @param  \Illuminate\Routing\Route  $route
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	public function route($name, $parameters = array(), $route = null)
	{
		$route = $route ?: $this->routes->getByName($name);

		$parameters = (array) $parameters;

		if ( ! is_null($route))
		{
			return $this->toRoute($route, $parameters);
		}
		else
		{
			throw new InvalidArgumentException("Route [{$name}] not defined.");
		}
	}

	/**
	 * Get the URL for a given route instance.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  mixed  $parameters
	 * @return string
	 */
	protected function toRoute($route, $parameters)
	{
		$domain = $this->getRouteDomain($route, $parameters);

		$path = preg_replace_sub('/\{.*?\}/', $parameters, $route->uri());

		return $this->trimUrl($this->getRouteRoot($route, $domain), $path);
	}

	/**
	 * Get the formatted domain for a given route.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  array  $parameters
	 * @return string
	 */
	protected function getRouteDomain($route, &$parameters)
	{
		return $route->domain() ? $this->formatDomain($route, $parameters) : null;
	}

	/**
	 * Format the domain and port for the route and request.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  array  $parameters
	 * @return string
	 */
	protected function formatDomain($route, &$parameters)
	{
		return $this->addPortToDomain(preg_replace_sub(

			'/\{.*?\}/', $parameters, $this->getDomainAndScheme($route)

		));
	}

	/**
	 * Get the domain and schee for the route.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @return string
	 */
	protected function getDomainAndScheme($route)
	{
		return $this->getScheme($route->secure()).$route->domain();
	}

	/**
	 * Add the port to the domain if necessary.
	 *
	 * @param  string  $domain
	 * @return string
	 */
	protected function addPortToDomain($domain)
	{
		if ($this->request->getPort() == '80')
		{
			return $domain;
		}
		else
		{
			return $domain .= ':'.$this->request->getPort();
		}
	}

	/**
	 * Get the root of the route URL.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  string  $domain
	 * @return string
	 */
	protected function getRouteRoot($route, $domain)
	{
		return $this->getRootUrl($this->getScheme($route->secure()), $domain);
	}

	/**
	 * Get the URL to a controller action.
	 *
	 * @param  string  $action
	 * @param  mixed   $parameters
	 * @param  bool    $absolute
	 * @return string
	 */
	public function action($action, $parameters = array(), $absolute = true)
	{
		return $this->route($action, $parameters, $this->routes->getByAction($action));
	}

	/**
	 * Get the base URL for the request.
	 *
	 * @param  string  $scheme
	 * @param  string  $root
	 * @return string
	 */
	protected function getRootUrl($scheme, $root = null)
	{
		$root = $root ?: $this->request->root();

		$start = starts_with($root, 'http://') ? 'http://' : 'https://';

		return preg_replace('~'.$start.'~', $scheme, $root, 1);
	}

	/**
	 * Determine if the given path is a valid URL.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function isValidUrl($path)
	{
		if (starts_with($path, array('#', '//', 'mailto:', 'tel:'))) return true;

		return filter_var($path, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * Format the given URL segments into a single URL.
	 *
	 * @param  string  $root
	 * @param  string  $path
	 * @param  string  $tail
	 * @return string
	 */
	protected function trimUrl($root, $path, $tail = '')
	{
		return trim($root.'/'.trim($path.'/'.$tail, '/'), '/');
	}

	/**
	 * Get the request instance.
	 *
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Set the current request instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

}
